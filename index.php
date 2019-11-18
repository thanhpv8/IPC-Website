<?php
include "login.html";



function getUrl() {
    $url = "";
    $file = fopen("bhd.cfg", "r");
    if ($file) {
        while (($line = fgets($file)) !== false) {
            // process the line read.
            $lineExtract = explode(":", $line);
            if($lineExtract[0] == "RUNNING") {
                $url = str_replace(' ','',$lineExtract[1]);
                break;
            }

        }

        fclose($file);

    }
    return $url;

}

?>

<script>

    $(document).ready(function() {
        var ipcDispatch;
        login.loginBtn.click(function(e) {
            e.preventDefault();
            <?php $folder = getUrl();?>
            var swfolder = <?php $folder ?>
            $("#ipcBody").append(<?php include "$folder/pages/nav-wrapper/nav-wrapper.php"; ?>)
            $("#ipcBody").append(<?php include "$folder/pages/modals.html"; ?>)
            $("#ipcBody").append(<?php include "$folder/js/functions.php"; ?>)
            $("#ipcBody").append(<?php include "$folder/js/variables.php"; ?>)

            ipcDispatch = swfolder+"/em/ipcDispatch.php";
            // login.submitLogin();
        
        });
    });


</script>
