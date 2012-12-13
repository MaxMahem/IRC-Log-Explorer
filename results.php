<?
/* 
 * Originial by terraserver.de/search-0.2-11.04.2002 - http://www.terraserver.de/
 * extensive modifications by Austin Stanley
 * 
 * Copyright (C) 2002 Holger Eichert, mailto:h.eichert@gmx.de. All rights reserved.
 * 
 */

$searchDirectory = dirname(__FILE__); // Directory to be searched
$searchFileTypes = array("log");      // Which files types should be searched?
define('MIN_CHARS', 3);   // Min. chars that must be entered to perform the search
define('MAX_CHARS', 30);  // Max  chars that can be entered to perform the search
define('HIT_LIMIT', 500); // limit of number of hits

function search_form() {
    $lastKeyword = filter_input(INPUT_GET, 'keyword');
    $case        = filter_input(INPUT_GET, 'case');
    $regx        = filter_input(INPUT_GET, 'regx');
    
    $defaultSearchValue = (!$lastKeyword) ? ''        : str_replace("&amp;","&",htmlentities($lastKeyword));
    $defaultCaseValue   = (isset($case))  ? 'CHECKED' : '';
    $defaultRegxValue   = (isset($regx))  ? 'CHECKED' : '';
    
    echo "<form action='results.php' method='GET'>";
    echo "<input type='hidden' value='SEARCH' name='action'>";
    echo "<input type='text' name='keyword' class='text' size='10'  maxlength='" . MAX_CHARS . "' value='$defaultSearchValue' placeholder='Search Term' required>"; 

    echo "<div class='type'>";
    echo "<input type='checkbox' name='case' value='true' id='case' $defaultCaseValue><label for='case'>Case Sensitive</label>";
    echo "<input type='checkbox' name='regx' value='true' id='regx' $defaultRegxValue><label for='regx'>Regular Expression</label>";
    echo "</div>";

    echo "<input type='submit' value='Search' class='button'>";
    echo "</form>";
}

function search_dir($searchDirectory, $searchFileTypes) {
    global $totalHitCount;
    
    $keyword = filter_input(INPUT_GET, 'keyword');
    $action  = filter_input(INPUT_GET, 'action');
    
    if($action == "SEARCH") {
        /* get extra necessary variables */
        $case = filter_input(INPUT_GET, 'case');
        $regx = filter_input(INPUT_GET, 'regx');

        /* set approviate variables */
        $caseRegex = ($case) ? '' : 'i'; /* regex case sensative marker */
        $htmlSafeKeyword = htmlspecialchars($keyword);
        $regxSafeKeyword = preg_quote($keyword);
        
        echo "<h1>Your search result for: '<em>$htmlSafeKeyword</em>'</h1>" . PHP_EOL;
        
        $directory = new DirectoryIterator($searchDirectory);
        
        echo "<ul class='files'>" . PHP_EOL; /* open file ul */
            
        foreach ($directory as $fileInfo) { /* directory parse loop */
            $fileName = $fileInfo->getFilename();
            $fileLink = urlencode($fileName);
            
            /* if we have hit the limit of hits, stop */
            if ($totalHitCount >= HIT_LIMIT) { break; }
                
            /* if our file is a directory, we recurse */
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $searchDirectories = array("$searchDirectory/$fileName();)");
                search_dir($searchDirectories, $searchFileTypes);
            }
                
            /* get the file Extension */
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                
            /* if our file is of the proper type, we search in it */
            if (in_array($fileExtension, $searchFileTypes)) { /* search conditional */                   
                /* open our file */
                $file = $fileInfo->openFile('r');
                
                $fileHitCount = 0;
                
                /* parse through the whole file line by line */
                foreach ($file as $lineNum => $line) { /* file parse loop */    
                    /* searches our file for the keywords, method as appropriate */
                    if ($regx) {
                        $do = preg_match("/$keyword/$caseRegex", $line);
                    } else {
                        if($case) { $do = strstr($line,  $keyword); }
                        else      { $do = stristr($line, $keyword); }
                    }
                    
                    /* if we got a hit on the line */
                    if ($do) {
                        $fileHitCount++;
                        $totalHitCount++;

                        $htmlSafeLine = htmlspecialchars($line);
                        $styledLine   = preg_replace("/($htmlSafeKeyword)/$caseRegex",'<strong>$1</strong>', $htmlSafeLine);
                        
                        $queryData = array(
                            'start' => $lineNum -2,
                            'end'   => $lineNum +2,
                            'query' => $lineNum,
                            'file'  => $fileName
                        );
                        
                        $query = http_build_query($queryData, '', '&amp;');
                            
                        $hit  = "<li class='hit' data-line='$lineNum'>";
                        $hit .= "<a href='exerpt.php?$query' class='getExerptLink'>$lineNum:</a> $styledLine";
                        $hit .= "</li>" . PHP_EOL;
                        
                        $hits[] = $hit;
                            
                        if ($totalHitCount >= HIT_LIMIT) { break; }
                    }
                } /* close file parse loop */
                    
                /* if we got a hit on the file we just searched */
                if ($fileHitCount > 0) {
                    echo "<li class='file' data-filename='$fileLink'>" . PHP_EOL; /* open file li */
                    echo "$fileHitCount Hits: <a href='$fileLink' class='file'>$fileName</a>" . PHP_EOL;
                        
                    echo "<ol class='hits'>";                                    /* open  hits ol */
                    foreach ($hits as $hit) { echo $hit; }                       /* list  hit  li */
                    echo "</ol>" . PHP_EOL;                                      /* close hits ol */
                    
                    echo "</li>" . PHP_EOL;                                      /* close file li */
                }
                    
                unset($hits);
                flush();
            } /* close search conditional */
        } /* close directory parse loop */
        
        echo "</ul>"; /* close files ul */
        
    if ($totalHitCount < 1) { echo "<p class='result'>Sorry, no hits.</p>"; }
    }
}

?>
<!DOCTYPE html>

<html>  
<head>  
    <meta charset='UTF-8'>    
    <title>IRC Log Search</title>
    <link rel="stylesheet" type="text/css" href="search.css" />
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="results.js"></script>
</head>

<body>
    <div id="container">  
        <header><h1>MaxMahem - IRC Log Files</h1></header>

        <div class="search">
        <fieldset>
            <legend>Search Text</legend>
                <?php search_form() ?>
            </fieldset>
<!--            <fieldset>
                <legend>Search Nick</legend>
            </fieldset>
            <fieldset>
                <legend>Search Date/Time</legend>
            </fieldset> -->
        </div>
        <div class="results">
            <? search_dir($searchDirectory, $searchFileTypes); ?>
        </div>
    </div>
</body>
</html>
