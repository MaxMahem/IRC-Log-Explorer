<?php

/* functions for searching */
require_once('includes/search.php');

/* get a directory iterator for our current path */
$logDirectory  = dirname(__FILE__) . '/../';
$directoryIterator = new DirectoryIterator($logDirectory);

function displayFiles(DirectoryIterator $fileInfo) {
    global $totalSize;
    global $totalCount;
    
    /* our version of php lacks getExtension, so we use this workaround */
    $fileExtension = pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION);
                        
    /* we only want to list the file if it's a log file currently */
    if ($fileExtension == 'log') {
        /* set the variables */
        $fileNameParts = explode('_', $fileInfo->getFilename());

        $network = $fileNameParts[1];
        $channel = $fileNameParts[2];
        $channelLink = '../' . preg_replace('/#/', '', $channel) . '.html';

        $fileName = $fileInfo->getFilename();
        $fileDate = substr($fileNameParts[3], 0, 4) . '/' . substr($fileNameParts[3], 4, 2) . '/' . substr($fileNameParts[3], 6, 2);
        $fileLink = urlencode($fileInfo->getFilename());
        $fileSize = round($fileInfo->getSize()/1024, 2) . 'KB';
        
        $totalSize  += $fileInfo->getSize();
        $totalCount += 1;

        $row  = '<tr>';
        $row .= "<td class='network'>$network</td>";
        if (file_exists($channelLink)) {
            $row .= "<td class='channel'><a href='$channelLink'>$channel</a></td>";
        } else {
            $row .= "<td class='channel'>$channel</td>";
        }
        $row .= "<td class='name'><a href='../$fileLink'>$fileName</a></td>";
        $row .= "<td class='date'>$fileDate</td>";
        $row .= "<td class='size'>$fileSize</td>";

        return $row;
    } else {
            return '';
    }
}

?>
<!DOCTYPE html>

<html>  
    <head>  
        <meta charset='UTF-8'>
        <title>IRC Log Files</title>
        <link rel="stylesheet" type="text/css" href="index.css" /> 
        <script src="sorttable.js"></script>
    </head>

    <body>  
        <div id="container">  
            <header><h1>MaxMahem - IRC Log Files</h1></header>

            <fieldset>
                <legend>Search</legend>
                <?php searchForm() ?>
            </fieldset>
            <fieldset>  
                <legend>Logs</legend>  

                <table class="files sortable">
                    <thead>
                        <tr><th>Network</th><th>Channel</th><th>File Name</th><th>Date</th><th class="size">Size</th></tr>
                    </thead>
                    <tbody>
<?php               
/* print the file rows */
foreach ($directoryIterator as $fileInfo) {
    echo displayFiles($fileInfo);
}
?>
                </tbody>
                <tfoot>
                    <tr><td></td><td></td><td>Total Count: <?=$totalCount;?></td><td colspan='2' class='size'>Total Size: <?= round($totalSize/1048576, 2) . 'MB'; ?></td></tr>
                </tfoot>
                </table>  
            </fieldset>

            <div style="clear:both;"></div>
        </div>
    </body>
</html>
