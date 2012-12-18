<?php
/* exerpt.php
 * does some magic and returns an exerpt of the selected file 
 */

/* get variables */
$fileName     = filter_input(INPUT_GET, 'file');
$fileNamePath = 'logs' . DIRECTORY_SEPARATOR . $fileName;
$startLineNum = filter_input(INPUT_GET, 'start');
$endLineNum   = filter_input(INPUT_GET, 'end');
$queryLineNum = filter_input(INPUT_GET, 'query');

/* AJAX check  */
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
  $ajax = TRUE;
} else {
  $ajax = FALSE;
}

/* if not an AJAX request, style the webpage */
if ($ajax == FALSE) {
    echo "<html>";
    echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<title>IRC Log Exerpt - $fileName</title>";
        echo "<link rel='stylesheet' type='text/css' href='/irc/search.css' />";
    echo "</head>";

    echo "<body>";
    echo "<div id='container'>";
    echo "<header>";
    echo "<h1>IRC Log Exerpt - $fileName</h1>";
    echo "</header>";
}
   
if (file_exists($fileNamePath)) {
    $file = new SplFileObject($fileNamePath, 'r');
       
    /* check that our queried lines fit within the range */
    if ($startLineNum < 0) { 
        $startLineNum = 0;
    }
    
    $file->seek($startLineNum);

    /* get the next 5 lines from the startPosition */
    while (($file->key() <= $endLineNum) && !$file->eof()) {
        $lines[$file->key()] = $file->current();
        $file->next();
    }
    
    $nextQueryData = array(
        'start' => $startLineNum,
        'end'   => $endLineNum + 6,
        'query' => $queryLineNum,
        'file'  => $fileName
    );
    $nextQuery = http_build_query($nextQueryData, '', '&amp;');

    $prevQueryData = array(
        'start' => $startLineNum - 6,
        'end'   => $endLineNum,
        'query' => $queryLineNum,
        'file'  => $fileName
    );
    $prevQuery = http_build_query($prevQueryData, '', '&amp;');
    
    /* check for start */
    if ($startLineNum == 0) {
        echo "START" . PHP_EOL;
    } else {
        /* link to get more from start */
        echo "<a href='/irc/exerpt?$prevQuery' class='getExerptLink'>MORE</a>" . PHP_EOL;
    }
    
    echo "<div class='exerpt'>" . PHP_EOL;
    echo "<ol class='exerpt' start='$startLineNum'>" . PHP_EOL;
    foreach ($lines as $lineNum => $line) {
        /* if our line number is the select line number we add the 'selected' class, to bold it. */
        $selected = ($lineNum == $queryLineNum) ? "class='selected'" : '';
    
        $htmlSafeLine = htmlspecialchars($line);
        echo "<li $selected data-line='$lineNum'><pre>$htmlSafeLine</pre></li>" . PHP_EOL;
    }
    echo "</ol>" . PHP_EOL;
    echo "</div>" . PHP_EOL;
    
    /* check for end */
    if ($file->eof()) {
        echo "END" . PHP_EOL;
    } else {
        /* link to get more from end */
        echo "<a href='/irc/exerpt?$nextQuery' class='getExerptLink'>MORE</a>" . PHP_EOL;
    }
}

/* close tags if not ajax request */
if ($ajax == FALSE) {
    echo "</div>";
    echo "</body>";
    echo "</html>";
}