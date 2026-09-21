<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function peticion($curl, $url, $datos = null)
{
    curl_setopt_array($curl, [CURLOPT_URL=>$url, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HEADER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>10,
        CURLOPT_POST=>$datos !== null, CURLOPT_COOKIEFILE=>'']);
    if ($datos !== null) { curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($datos)); }
    $resultado=curl_exec($curl);
    if ($resultado===false) { throw new RuntimeException('HTTP: '.curl_error($curl)); }
    $cabeceras=substr($resultado,0,curl_getinfo($curl,CURLINFO_HEADER_SIZE));
    $body=substr($resultado,curl_getinfo($curl,CURLINFO_HEADER_SIZE));
    preg_match('/^Location:\s*(.+)$/mi',$cabeceras,$destino);
    return ['codigo'=>curl_getinfo($curl,CURLINFO_RESPONSE_CODE),'destino'=>trim($destino[1]??''),'body'=>$body,'headers'=>$cabeceras];
}

function formularioInstitucion($html)
{
    preg_match('/name="institucion_csrf" value="([^"]+)"/',$html,$csrf);
    preg_match('/name="institucion_version" value="([^"]+)"/',$html,$version);
    return ['institucion_csrf'=>$csrf[1]??'', 'institucion_version'=>$version[1]??''];
}

function csrfRegistro($html)
{
    preg_match('/name="registro_csrf" value="([^"]+)"/', $html, $csrf);
    return $csrf[1] ?? '';
}

function csrfRecuperacion($html)
{
    preg_match('/name="recuperacion_csrf" value="([^"]+)"/', $html, $csrf);
    return $csrf[1] ?? '';
}

function levantarServidor($modo, $activo = true)
{
    $socket=stream_socket_server('tcp://127.0.0.1:0',$errno,$error);
    if (!$socket) { throw new RuntimeException('No se pudo reservar puerto local'); }
    $direccion=stream_socket_get_name($socket,false); fclose($socket);
    $directorio=sys_get_temp_dir().DIRECTORY_SEPARATOR.'campus_mt_http_'.bin2hex(random_bytes(8));
    mkdir($directorio,0700);
    $entorno=array_merge(getenv(),['DB_HOST'=>DB_HOST,'DB_PORT'=>DB_PORT,'DB_NAME'=>DB_NAME,'DB_USER'=>DB_USER,'DB_PASSWORD'=>DB_PASSWORD,
        'WP_DB_HOST'=>DB_HOST,'WP_DB_PORT'=>DB_PORT,'WP_DB_NAME'=>DB_NAME,'WP_DB_USER'=>DB_USER,'WP_DB_PASSWORD'=>DB_PASSWORD,
        'WP_TABLE_PREFIX'=>'prueba_wp_','WP_ROOT_PATH'=>'','AUTH_MODE'=>$modo,'AUTH_DEBUG'=>'0',
        'INSTITUCIONES_CONTEXTO_ACTIVO'=>$activo?'1':'0']);
    // Fijar la base sintética antes de config.local.php: nunca dirigir pruebas HTTP a datos locales.
    $bootstrap = $directorio . '/config-prueba.php';
    file_put_contents($bootstrap, '<?php foreach (["DB_HOST","DB_PORT","DB_NAME","DB_USER","DB_PASSWORD","WP_DB_HOST","WP_DB_PORT","WP_DB_NAME","WP_DB_USER","WP_DB_PASSWORD","WP_TABLE_PREFIX","WP_ROOT_PATH","AUTH_MODE"] as $key) { define($key, getenv($key)); } define("INSTITUCIONES_CONTEXTO_ACTIVO", getenv("INSTITUCIONES_CONTEXTO_ACTIVO")==="1");');
    $proceso=proc_open([PHP_BINARY,'-d','display_errors=0','-d','session.save_path='.$directorio,
        '-d','auto_prepend_file='.$bootstrap,
        '-d','session.serialize_handler=php_serialize','-S',$direccion,'-t',dirname(__DIR__,2)],
        [0=>['pipe','r'],1=>['file',$directorio.'/servidor.log','a'],2=>['file',$directorio.'/servidor.log','a']],$pipes,dirname(__DIR__,2),$entorno);
    if (!is_resource($proceso)) { throw new RuntimeException('No se pudo iniciar PHP local'); }
    fclose($pipes[0]);
    for ($i=0;$i<40;$i++) {
        $conexion=@stream_socket_client('tcp://'.$direccion,$errno,$error,.1);
        if ($conexion) { fclose($conexion); return [$proceso,'http://'.$direccion.'/index.php',$directorio]; }
        usleep(100000);
    }
    proc_terminate($proceso); proc_close($proceso);
    throw new RuntimeException('PHP no inició en el puerto local');
}

function archivoSesion($curl, $directorio)
{
    foreach (curl_getinfo($curl,CURLINFO_COOKIELIST) as $cookie) {
        $partes=explode("\t",$cookie);
        if (($partes[5]??'')==='PHPSESSID' && preg_match('/^[A-Za-z0-9,-]+$/',$partes[6]??'')) {
            return $directorio.'/sess_'.$partes[6];
        }
    }
    throw new RuntimeException('No se recibió cookie de sesión');
}

foreach (['LOCAL','WORDPRESS','HYBRID'] as $modo) {
    [$servidor,$url,$sesiones]=levantarServidor($modo);
    $curl=curl_init();
    try {
        $r=peticion($curl,$url.'?r=seleccionar-institucion');
        verificar($r['destino']==='index.php?r=login',"$modo: selección requiere autenticación");
        $r=peticion($curl,$url.'?r=login');
        verificar(
            str_contains($r['body'],'Olvidé mi contraseña')
                && (str_contains($r['body'],'index.php?r=registro') === ($modo !== 'WORDPRESS')),
            "$modo: login muestra el acceso al registro solo cuando corresponde"
        );
        verificar(
            stripos($r['headers'], 'Content-Security-Policy:') !== false
                && stripos($r['headers'], 'X-Content-Type-Options: nosniff') !== false
                && stripos($r['headers'], 'Referrer-Policy: strict-origin-when-cross-origin') !== false,
            "$modo: respuestas dinámicas incluyen cabeceras de seguridad"
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Sec-Fetch-Site: cross-site', 'Origin: https://ataque.example']);
        $cruzada=peticion($curl,$url.'?r=login',['login_email'=>'a@campus.example','login_pass'=>'irrelevante']);
        curl_setopt($curl, CURLOPT_HTTPHEADER, []);
        verificar($cruzada['codigo']===403 && str_contains($cruzada['body'],'validación de origen'),"$modo: POST cruzado bloqueado antes de autenticar");
        $r=peticion($curl,$url.'?r=registro');
        if ($modo === 'WORDPRESS') {
            verificar($r['codigo']===200 && !str_contains($r['body'],'name="accion_registro"'),'WORDPRESS: registro directo deshabilitado');
        } else {
            $csrfRegistro=csrfRegistro($r['body']);
            verificar($r['codigo']===200 && $csrfRegistro!=='',"$modo: formulario de registro público disponible");
            $emailRegistro='registro-'.strtolower($modo).'@campus.example';
            $claveRegistro='Registro-'.bin2hex(random_bytes(6));
            $r=peticion($curl,$url.'?r=registro',[
                'accion_registro'=>'crear_cuenta',
                'registro_csrf'=>$csrfRegistro,
                'website'=>'',
                'registro_nombre'=>'Ada',
                'registro_apellido'=>'Lovelace',
                'registro_email'=>$emailRegistro,
                'registro_password'=>$claveRegistro,
                'registro_password_confirmacion'=>$claveRegistro,
            ]);
            verificar($r['codigo']===303 && $r['destino']==='index.php?r=sin-acceso-institucional',"$modo: alta inicia sesión sin inventar acceso institucional");
            $stmtRegistro=$pdo->prepare('SELECT idUsuario,pass,rol,activo,origenAuth FROM usuarios WHERE email=?');
            $stmtRegistro->execute([$emailRegistro]);
            $usuarioRegistro=$stmtRegistro->fetch(PDO::FETCH_ASSOC);
            verificar(
                $usuarioRegistro && (int)$usuarioRegistro['activo']===1 && $usuarioRegistro['rol']===''
                    && $usuarioRegistro['origenAuth']==='LOCAL' && password_verify($claveRegistro,$usuarioRegistro['pass']),
                "$modo: identidad registrada queda activa con contraseña segura y origen local"
            );
            $stmtMembresias=$pdo->prepare('SELECT COUNT(*) FROM usuarios_instituciones WHERE id_usuario=?');
            $stmtMembresias->execute([(int)$usuarioRegistro['idUsuario']]);
            verificar((int)$stmtMembresias->fetchColumn()===0,"$modo: registro no concede membresías ni roles");
            $r=peticion($curl,$url.'?r=sin-acceso-institucional');
            verificar(str_contains($r['body'],'Tu cuenta fue creada correctamente.'),"$modo: el alta explica el siguiente paso");
            peticion($curl,$url.'?r=logout');
        }
        $claveLogin=$modo==='HYBRID'?'Wp-fallback-'.bin2hex(random_bytes(5)):$password;
        if ($modo==='HYBRID') {
            $pdo->prepare('UPDATE prueba_wp_users SET user_pass=? WHERE ID=101')->execute([password_hash($claveLogin,PASSWORD_DEFAULT)]);
        }
        $r=peticion($curl,$url.'?r=login',['login_email'=>'a@campus.example','login_pass'=>$claveLogin]);
        verificar($r['codigo']===303 && $r['destino']==='index.php?r=seleccionar-institucion',"$modo: login real llega a selección");
        $r=peticion($curl,$url.'?r=seleccionar-institucion');
        verificar(str_contains($r['body'],'MenteMotion') && str_contains($r['body'],'Instituto Demo') && str_contains($r['headers'],'no-store'),"$modo: tarjetas y respuesta sin caché");
        $form=formularioInstitucion($r['body']);
        $r=peticion($curl,$url.'?r=seleccionar-institucion',$form+['id_institucion'=>$demo]);
        verificar($r['codigo']===303 && $r['destino']==='index.php',"$modo: selección válida abre el Campus");
        $sesion=unserialize(file_get_contents(archivoSesion($curl,$sesiones)),['allowed_classes'=>false]);
        verificar((int)$sesion['institucion_id']===$demo && $sesion['institucion_roles']===['ESTUDIANTE'],"$modo: sesión conserva el rol institucional correcto");
        $r=peticion($curl,$url.'?r=seleccionar-institucion',$form+['id_institucion'=>$mm]);
        verificar($r['codigo']===403,"$modo: formulario reutilizado rechazado");
        $r=peticion($curl,$url.'?r=institucion-preparada');
        verificar($r['codigo']===303 && $r['destino']==='index.php',"$modo: la ruta preventiva anterior vuelve al Campus");
        $r=peticion($curl,$url.'?r=seleccionar-institucion&id_institucion='.$mm);
        $sesion=unserialize(file_get_contents(archivoSesion($curl,$sesiones)),['allowed_classes'=>false]);
        verificar((int)$sesion['institucion_id']===$demo,"$modo: GET no cambia institución");
        $sesion['ultima_actividad']=time()-SESSION_INACTIVITY_TIMEOUT-10;
        file_put_contents(archivoSesion($curl,$sesiones),serialize($sesion));
        $r=peticion($curl,$url.'?r=seleccionar-institucion');
        verificar($r['destino']==='index.php?r=login',"$modo: sesión vencida regresa a login");
        $r=peticion($curl,$url.'?r=forgot');
        verificar($r['codigo']===200,"$modo: ruta de recuperación responde");
        if ($modo==='WORDPRESS') {
            verificar(str_contains($r['body'],'mentemotion.com') && !str_contains($r['body'],'name="forgot_email"'), 'WORDPRESS: recuperación continúa delegada a WordPress');
        } else {
            $csrfRecuperacion=csrfRecuperacion($r['body']);
            verificar($csrfRecuperacion!=='' && str_contains($r['body'],'name="forgot_email"'),"$modo: recuperación local usa CSRF");
            $r=peticion($curl,$url.'?r=forgot',['accion_solicitar_recuperacion'=>1,'recuperacion_csrf'=>$csrfRecuperacion,'forgot_email'=>'inexistente@campus.example']);
            verificar(str_contains($r['body'],'Si existe una cuenta local activa'),"$modo: recuperación no enumera cuentas");
        }
        if ($modo==='LOCAL') {
            $r=peticion($curl,$url.'?r=login',['login_email'=>'c@campus.example','login_pass'=>$password]);
            verificar($r['destino']==='index.php','LOCAL: una membresía abre el Campus automáticamente');
            $r=peticion($curl,$url.'?r=seleccionar-institucion'); $form=formularioInstitucion($r['body']);
            $r=peticion($curl,$url.'?r=seleccionar-institucion',$form+['id_institucion'=>$demo]);
            verificar($r['codigo']===403,'LOCAL: C no puede seleccionar Demo por HTTP');
            peticion($curl,$url.'?r=logout');
            $r=peticion($curl,$url.'?r=login',['login_email'=>'d@campus.example','login_pass'=>$password]);
            verificar($r['destino']==='index.php?r=sin-acceso-institucional','LOCAL: cuenta sin membresías recibe pantalla específica');
            $r=peticion($curl,$url.'?r=sin-acceso-institucional');
            verificar(str_contains($r['body'],'no tenés una membresía activa'),'LOCAL: pantalla sin acceso explica el estado');
        }
        peticion($curl,$url.'?r=logout');
        $r=peticion($curl,$url.'?r=seleccionar-institucion');
        verificar($r['destino']==='index.php?r=login',"$modo: logout destruye acceso institucional");
    } finally {
        curl_close($curl); proc_terminate($servidor); proc_close($servidor);
    }
}

[$servidor,$url,$sesiones]=levantarServidor('LOCAL',false);
$curl=curl_init();
try {
    $r=peticion($curl,$url.'?r=login',['login_email'=>'c@campus.example','login_pass'=>$password]);
    verificar($r['codigo']===302 && $r['destino']==='index.php','Modo desactivado preserva destino del login legacy');
    $sesion=unserialize(file_get_contents(archivoSesion($curl,$sesiones)),['allowed_classes'=>false]);
    verificar($sesion['usuario']['rol']==='ADMINISTRADOR' && !isset($sesion['institucion_id']),'Modo desactivado preserva sesión legacy sin contexto institucional');
} finally {
    curl_close($curl); proc_terminate($servidor); proc_close($servidor);
}

$pdo->exec("DELETE FROM campus_migraciones WHERE codigo='multi_institucion_01_expandir'");
[$servidor,$url,$sesiones]=levantarServidor('LOCAL');
$curl=curl_init();
try {
    $r=peticion($curl,$url.'?r=login');
    verificar($r['codigo']===503 && !str_contains($r['body'],'SQLSTATE'),'Esquema no migrado falla cerrado sin exponer SQL');
} finally {
    curl_close($curl); proc_terminate($servidor); proc_close($servidor);
    $pdo->exec("INSERT INTO campus_migraciones(codigo) VALUES ('multi_institucion_01_expandir')");
}
