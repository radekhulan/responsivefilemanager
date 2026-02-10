<?php
header('X-Content-Type-Options: nosniff');

// Pokud není co nahrávat, vrátit prázdnou odpověď
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || (empty($_FILES) && empty($_POST['url']) && !isset($_POST['submit']))) {
    header('Content-Type: application/json');
    echo json_encode(array('files' => array()));
    exit;
}

try {
    if (!isset($config)) {
        $config = require 'config/config.php';
    }

    include 'include/utils.php';

    if (($_SESSION['RF']["verify"] ?? '') !== "RESPONSIVEfilemanager") {
        response(trans('forbidden') . AddErrorLocation(), 403)->send();
        exit;
    }

    // CSRF protection
    if (!verifyCsrfToken()) {
        response(trans('forbidden') . ' (CSRF)' . AddErrorLocation(), 403)->send();
        exit;
    }

    include 'include/mime_type_lib.php';

    $source_base = $config['current_path'];
    $thumb_base = $config['thumbs_base_path'];

    if (isset($_POST["fldr"])) {
        $_POST['fldr'] = str_replace('undefined', '', $_POST['fldr']);
        $storeFolder = $source_base . $_POST["fldr"];
        $storeFolderThumb = $thumb_base . $_POST["fldr"];
    } else {
        return;
    }

    $fldr = rawurldecode(trim(strip_tags($_POST['fldr']), "/") . "/");

    if (!checkRelativePath($fldr)) {
        response(trans('wrong path') . AddErrorLocation())->send();
        exit;
    }

    // Validate upload paths are within allowed directories
    if (!validatePathSecurity($storeFolder, $config)) {
        response(trans('wrong path') . AddErrorLocation(), 403)->send();
        exit;
    }
    if (!validatePathSecurity($storeFolderThumb, $config)) {
        response(trans('wrong path') . AddErrorLocation(), 403)->send();
        exit;
    }

    $path = $storeFolder;
    $cycle = true;
    $max_cycles = 50;
    $i = 0;
    //GET config
    while ($cycle && $i < $max_cycles) {
        $i++;
        if ($path == $config['current_path']) {
            $cycle = false;
        }
        if (file_exists($path . "config.php")) {
            $configTemp = include $path . 'config.php';
            $config = array_merge($config, $configTemp);
            //TODO switch to array
            $cycle = false;
        }
        $path = fix_dirname($path) . '/';
    }

    require('UploadHandler.php');
    $messages = null;
    if (trans("Upload_error_messages") !== "Upload_error_messages") {
        $messages = trans("Upload_error_messages");
    }

    // make sure the length is limited to avoid DOS attacks
    if (isset($_POST['url']) && strlen($_POST['url']) < 2000) {
        $url = $_POST['url'];
        $urlPattern = '/^https?:\/\/[^\s<>"{}|\\^`\[\]]+$/i';

        if (preg_match($urlPattern, $url)) {
            $temp = tempnam(sys_get_temp_dir(), 'RF');

            $ch = curl_init($url);
            $fp = fopen($temp, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                fclose($fp);
                throw new Exception('cURL error: ' . $error);
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            fclose($fp);

            $size = filesize($temp);

            if ($httpCode !== 200) {
                throw new Exception('HTTP error: ' . $httpCode);
            }
            $_FILES['files'] = array(
                'name' => array(basename($_POST['url'])),
                'tmp_name' => array($temp),
                'size' => array($size),
                'type' => array(null),
                'error' => array(UPLOAD_ERR_OK)
            );
        } else {
            throw new Exception('Is not a valid URL.');
        }
    }

    // Ověření, že soubor byl skutečně nahrán
    if (empty($_FILES['files']['tmp_name'][0])) {
        $error_message = trans('Upload_error_messages')[4] ?? 'No file was uploaded';
        echo json_encode(array("files" => array(array(
            'name' => $_FILES['files']['name'][0] ?? '',
            'error' => $error_message . AddErrorLocation(),
            'size' => 0,
            'type' => ''
        ))));
        exit();
    }

    if ($config['mime_extension_rename']) {
        $info = pathinfo($_FILES['files']['name'][0]);
        $mime_type = $_FILES['files']['type'][0];
        if (function_exists('mime_content_type')) {
            $mime_type = mime_content_type($_FILES['files']['tmp_name'][0]);
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['files']['tmp_name'][0]);
        } else {
            $mime_type = get_file_mime_type($_FILES['files']['tmp_name'][0]);
        }
        $extension = get_extension_from_mime($mime_type);

        if ($extension == 'so' || $extension == '' || $mime_type == "text/troff") {
            $extension = $info['extension'];
        }
        $filename = $info['filename'] . "." . $extension;
    } else {
        $filename = $_FILES['files']['name'][0];
    }
    $_FILES['files']['name'][0] = fix_filename($filename, $config);

    if(empty($_FILES['files']['type'][0])){
        $_FILES['files']['type'][0] = $mime_type;
    }
    // LowerCase
    if ($config['lower_case']) {
        $_FILES['files']['name'][0] = fix_strtolower($_FILES['files']['name'][0]);
    }
    if (!checkresultingsize($_FILES['files']['size'][0])) {
        $response = new stdClass();
        $response->files = array();
        $response->files[0] = new stdClass();
        $response->files[0]->error = sprintf(trans('max_size_reached'), $config['MaxSizeTotal']) . AddErrorLocation();
        echo json_encode($response);
        exit();
    }

    $uploadConfig = array(
        'config' => $config,
        'storeFolder' => $storeFolder,
        'storeFolderThumb' => $storeFolderThumb,
        'upload_dir' => dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $storeFolder,
        'upload_url' => $config['base_url'] . $config['upload_dir'] . $_POST['fldr'],
        'mkdir_mode' => $config['folderPermission'],
        'max_file_size' => $config['MaxSizeUpload'] * 1024 * 1024,
        'correct_image_extensions' => true,
        'print_response' => false
    );

    if (!$config['ext_blacklist']) {
        $uploadConfig['accept_file_types'] = '/\.(' . implode('|', $config['ext']) . ')$/i';

        if ($config['files_without_extension']) {
            $uploadConfig['accept_file_types'] = '/((\.(' . implode('|', $config['ext']) . ')$)|(^[^.]+$))$/i';
        }
    } else {
        $uploadConfig['accept_file_types'] = '/\.(?!' . implode('|', $config['ext_blacklist']) . '$)/i';

        if ($config['files_without_extension']) {
            $uploadConfig['accept_file_types'] = '/((\.(?!' . implode('|', $config['ext_blacklist']) . '$))|(^[^.]+$))/i';
        }
    }

    //print_r($_FILES);die();
    $upload_handler = new UploadHandler($uploadConfig, true, $messages);
} catch (Exception $e) {
    $return = array();

    if (!empty($_FILES['files'])) {
        foreach ($_FILES['files']['name'] as $i => $name) {
            $return[] = array(
                'name' => $name,
                'error' => $e->getMessage(),
                'size' => $_FILES['files']['size'][$i],
                'type' => $_FILES['files']['type'][$i]
            );
        }

        echo json_encode(array("files" => $return));
        return;
    }

    echo json_encode(array("error" => $e->getMessage()));
}
