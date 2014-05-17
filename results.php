<?
/* functions for searching */
require_once('includes/search.php');
$searchDirectory = dirname(__FILE__) . '/../';
// $_SERVER['DOCUMENT_ROOT'] . '/irc/logs'; // Directory to be searched
$searchFileTypes = array("log");                // Type of files to be searched?
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
                <?php searchForm() ?>
            </fieldset>
        </div>
        <div class="results">
            <? searchDirectory($searchDirectory, $searchFileTypes); ?>
        </div>
    </div>
</body>
</html>
