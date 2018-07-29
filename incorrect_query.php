<?php
ini_set('memory_limit', '-1');

include 'SpellCorrector.php';

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', '32M');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$spellcheck_query= "";
$results = false;

if ($query)
{
  // The Apache Solr Client library should be on the include path
  // which is usually most easily accomplished by placing in the
  // same directory as this script ( . or current directory is a default
  // php include path entry in the php.ini)
  require_once('solr-php-client/Apache/Solr/Service.php');
 // require_once('spellcorrector-2008-10-07/SpellCorrector.php');

  // create a new solr service instance - host, port, and webapp
  // path (all defaults in this example)
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/fox_news');

  // SpellCheck
  $q = explode(" ", $query);
  for($i=0; $i < sizeof($q); ++$i)
  {
      $spellcheck_query .= SpellCorrector::correct($q[$i]).' ';
  }


  // if magic quotes is enabled then stripslashes will be needed
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }

  // in production code you'll always want to use a try /catch for any
  // possible exceptions emitted  by searching (i.e. connection
  // problems or a query parsing error)
  try
  {
	$spellcheck_query = rtrim($spellcheck_query);
	if($_GET['algo'] == "lucene"){
	    $results = $solr->search($query, 0, $limit);	
	}
	else{
	    $additionalParameters=array(
		   'sort'=>'pageRankFile desc',
	    );
	    $results = $solr->search($query, 0, $limit, $additionalParameters);
	}
  }
  catch (Exception $e)
  {
    // in production you'd probably log or email this error to an admin
    // and then show a special message to the user but for this example
    // we're going to show the full exception
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }
}

?>
<html>
  <head>
    <title>PHP Solr Client Example</title>
  </head>
  <body>
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>" required/>
      <input type="radio" name="algo" <?php if(isset($_GET['algo']) && $_GET['algo']=="lucene") echo "checked";?> value="lucene" required> Lucene </input>
      <input type="radio" name="algo" <?php if(isset($_GET['algo']) && $_GET['algo']=="pageRank") echo "checked";?> value="pageRank"> PageRank </input>
      &nbsp;   &nbsp;
      <input type="submit" />
    </form>
<?php

// display results
	
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);


	if($spellcheck_query != $query) { 
		$spellcheck_link = "http://localhost/query.php?q=".$spellcheck_query."&algo=".$_GET['algo'];

?>
	    <span style="font-size: 95%;"> <i> Did you mean: <a href=" <?php echo $spellcheck_link; ?>" style="text-decoration:none"> <?php echo htmlspecialchars($spellcheck_query, ENT_QUOTES, 'utf-8');  ?> </a> </i> </span><br /> <?php
	}


?>

    <?php 
	if($total == 0){ ?>
		<br><div> No Results Found </div>	<?php
	}
	else {
	?>
        <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div> <br>
        <ol>
	<?php
	  // iterate result documents
	  foreach ($results->response->docs as $doc)
	  {
	?>
	    <li>
		TITLE: <a href="<?php echo htmlspecialchars($doc->og_url,ENT_NOQUOTES,'utf-8'); ?>" style="text-decoration:none"> <?php echo htmlspecialchars($doc->title,ENT_NOQUOTES,'utf-8'); ?> </a><br>
		URL: <a href="<?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?>"> <?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?></a> <br>
<!--		ID: <?php echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); ?> <br>
		DESCRIPTION: <?php 
			if(empty($doc->og_description)){
				$desc= "N/A";
			}
			else{
				if(is_array($doc->og_description)){
					$desc=implode(",", $doc->og_description);
				}
				else{
					$desc=$doc->og_description;
				}
			}
			echo htmlspecialchars($desc, ENT_NOQUOTES, 'utf-8'); ?><br/> <br> <br>-->
	    </li>
	<?php
	  }
	?>
    	</ol>
	<?php
	}
}
?>
  </body>
</html>
