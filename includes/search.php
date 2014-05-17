<?php
/* 
 * Originial by terraserver.de/search-0.2-11.04.2002 - http://www.terraserver.de/
 * Extensive modifications by Austin Stanley - http://maxmahem.net/
 * 
 * Copyright (C) 2002 Holger Eichert, mailto:h.eichert@gmx.de. All rights reserved.
 * Copyright (C) 2012 Austin Stanley, mailto:maxtmahem@gmail.com
 */

define('MIN_CHARS', 3);   // Min. chars that must be entered to perform the search
define('MAX_CHARS', 30);  // Max  chars that can be entered to perform the search
define('HIT_LIMIT', 500); // limit of number of hits

function searchForm() {
    $lastKeyword = filter_input(INPUT_GET, 'keyword');
    $case        = filter_input(INPUT_GET, 'case');
    $regx        = filter_input(INPUT_GET, 'regx');
    $join        = filter_input(INPUT_GET, 'join');

    $defaultSearchValue = (!$lastKeyword) ? ''        : str_replace("&amp;","&",htmlentities($lastKeyword));
    $defaultCaseValue   = (isset($case))  ? 'CHECKED' : '';
    $defaultRegxValue   = (isset($regx))  ? 'CHECKED' : '';
    $defaultJoinValue   = (isset($join))  ? 'CHECKED' : '';

    echo "<form action='results.php' method='GET'>";
    echo "<input type='hidden' value='SEARCH' name='action'>";
    echo "<input type='text' name='keyword' class='text' size='10'  maxlength='" . MAX_CHARS . "' value='$defaultSearchValue' placeholder='Search Term' required>"; 

    echo "<div class='type'>";
    echo "<input type='checkbox' name='case' value='true' id='case' $defaultCaseValue><label for='case'>Case Sensitive</label>";
    echo "<input type='checkbox' name='regx' value='true' id='regx' $defaultRegxValue><label for='regx'>Regular Expression</label>";
    echo "<input type='checkbox' name='join' value='true' id='join' $defaultJoinValue><label for='join'>Include Channel Messages</label>";
    echo "</div>";

    echo "<input type='submit' value='Search' class='button'>";
    echo "</form>";
}

/**
 * linkify a string
 * code adapted from http://code.seebz.net/p/autolink-php/
 */
function autoLink($string, $attributeArray=array()) {
    $attributes = '';

    foreach ($attributeArray as $attribute => $value) {
        $attributes .= " {$attribute}=\"{$value}\"";
    }

    $string = ' ' . $string;
    $string = preg_replace(
        '`([^"=\'>])((http|https|ftp)://[^\s<]+[^\s<\.)])`i',
        '$1<a href="$2"'.$attributes.'>$2</a>',
        $string
    );

    $string = substr($string, 1);

    return $string;
}

function searchDirectory($searchDirectory, $searchFileTypes) {
    /* this is global so that recursion can work correctly, since that is turned off, this is to */
    // global $totalHitCount;
    $totalHitCount = 0;
    $startTime = microtime(TRUE);

    $keyword = filter_input(INPUT_GET, 'keyword');
    $case    = filter_input(INPUT_GET, 'case');
    $regx    = filter_input(INPUT_GET, 'regx');

    if (empty($keyword)) {
        die();
    }

    /* set approviate variables */
    $caseRegex = ($case) ? '' : 'i'; /* regex case sensative marker */
    $htmlSafeKeyword = htmlspecialchars($keyword);
    $regxSafeKeyword = preg_quote($htmlSafeKeyword);

    echo "<h1>Your search result for: '<em>$htmlSafeKeyword</em>'</h1>" . PHP_EOL;

    $directory = new DirectoryIterator($searchDirectory);

    foreach ($directory as $fileInfo) { /* directory parse loop */
        $fileName     = $fileInfo->getFilename();
        $fileLink     = urlencode($fileName);

        /* if we have hit the limit of hits, stop */
        if ($totalHitCount >= HIT_LIMIT) { break; }

        /* if our file is a directory, we recurse - disabled for now */
        /* if ($fileInfo->isDir() && !$fileInfo->isDot()) {
            $searchDirectories = array("$searchDirectory/$fileName();)");
            searchDirectory($searchDirectories, $searchFileTypes);
        } */

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
                    $linkedLine   = autoLink($htmlSafeLine);
                    $styledLine   = preg_replace("/($regxSafeKeyword)/$caseRegex",'<strong>$1</strong>', $linkedLine);

                    $queryData = array(
                        'start' => $lineNum -2,
                        'end'   => $lineNum +2,
                        'query' => $lineNum,
                        'file'  => $fileName
                    );

                    $query = http_build_query($queryData, '', '&amp;');

                    $hit  = "<li class='hit' data-line='$lineNum'>";
                    $hit .= "<a href='exerpt?$query' class='getExerptLink'>$lineNum:</a> $styledLine";
                    $hit .= "</li>" . PHP_EOL;

                    $hits[] = $hit;

                    if ($totalHitCount >= HIT_LIMIT) { break; }
                }
            } /* close file parse loop */

            /* if we got a hit on the file we just searched */
            if ($fileHitCount > 0) {
                $outputLine  = "<li class='file' data-filename='$fileLink'>" . PHP_EOL; /* open file li */
                $outputLine .= "$fileHitCount Hits: <a href='../$fileLink' class='file'>$fileName</a>" . PHP_EOL;

                $outputLine .= "<ol class='hits'>";                                     /* open  hits ol */
                foreach ($hits as $hit) {
                    $outputLine .= $hit;                                                /* list  hit  li */
                }
                $outputLine .= "</ol>" . PHP_EOL;                                       /* close hits ol */

                $outputLine .= "</li>" . PHP_EOL;                                       /* close file li */

                /* append to our output */
                $outputLines[] = $outputLine;
            }
                    
            unset($hits);
        } /* close search conditional */
    } /* close directory parse loop */
    
    $endTime = microtime(TRUE);
    $time = round($endTime - $startTime, 2);
    
    /* output data */
    if (!empty($outputLines)) {
        echo "<ul class='files'>" . PHP_EOL; /* open file ul */
        foreach ($outputLines as $outputLine) {
            echo $outputLine;
        }
        echo "</ul>";
    }
        
    /* hit counter */
    echo "<h1>";
    if ($totalHitCount < 1) {
        echo "Sorry, no hits.";
    } else {
        echo "Total Hits: $totalHitCount";
        if ($totalHitCount >= HIT_LIMIT) { echo "<br>Hit Limit Reached."; }
    }
    echo "<br>In $time seconds.";
    echo "</h1>";
}
