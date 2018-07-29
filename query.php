<?php
ini_set('memory_limit', '-1');

include 'SpellCorrector.php';

// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');
ini_set('memory_limit', '32M');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$query_as_in_textfield = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
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
  $query_as_in_textfield = $query;
  $query = strtolower($query);
  $q = explode(" ", $query);
  for($i = 0; $i < sizeof($q); ++$i)
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
	    $results = $solr->search($spellcheck_query, 0, $limit);
	}
	else{
	    $additionalParameters=array(
		   'sort'=>'pageRankFile desc',
	    );
	    $results = $solr->search($spellcheck_query, 0, $limit, $additionalParameters);
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
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="//code.jquery.com/jquery-1.12.4.js"></script>
    <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

<!-- AutoComplete Feature-->

	<script>
	    $(function(){
		var url_prefix = "http://localhost:8983/solr/fox_news/suggest?q=";
		var url_suffix = "&wt=json";
		$( "#q" ).autocomplete({
			source: function(request, response){
				var input = $("#q").val();
				var i=input.length-1;
				var str = "";
				while(i >=0 && input.charAt(i)!=' '){
					str = str.concat(input.charAt(i));
					i--;
				}
				var prev_query_term="";
				if(i > 0)
					prev_query_term = input.substring(0, i);
				var query_term = str.split("").reverse().join("");
				if(query_term == "")
				{
					query_term = prev_query_term;
					prev_query_term = "";
				}
				//console.log(query_term);
				var URL = url_prefix + query_term + url_suffix;
				$.ajax({
					url:URL,
					success : function(result){
						console.log(result);
						var jsonData = result.suggest.suggest;
//						var  = JSON.parse(suggestions);
						var suggestions = jsonData[query_term]['suggestions'];
						response($.map(suggestions, function(value, key) {
							if(prev_query_term=="")
								value = value.term;
							else
								value = prev_query_term + ' ' + value.term;
							return {
								label : value
							}
						}));
					},
					dataType : 'jsonp',
					jsonp : 'json.wrf'

				});
			},
			minLength : 1
		})
	});

	</script>

    <style>

	.mod {
	    padding-left: 16px;
	    padding-right: 16px;
	}
	.qtR3Y {
	    overflow: hidden;
	    padding-bottom: 20px;
            padding-top: 15px;
	}
	div{
	    display: block;
	}
	.kp-blk {
	    margin-left: 25px;
	    margin-right: 10px;
	    position: relative;
	}
	.kp-blk {
	    float:left; width: 690px; height: 150px;
	    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.16), 0 0 0 1px rgba(0,0,0,0.08);
	    box-shadow: 0 2px 2px 0 rgba(0,0,0,0.16), 0 0 0 1px rgba(0,0,0,0.08);
	    border-radius: 2px;
	}
	.outer{
	   width: 100%;
	}
	.hidden-box{
	    margin-left: 25px;
	    margin-right: 10px;
	    position: relative;
	    float:left; width: 690px; height: 150px;
	}
    </style>


  </head>
  <body>
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query_as_in_textfield, ENT_QUOTES, 'utf-8'); ?>" required/>
      <input type="radio" name="algo" <?php echo "checked";?> value="lucene" required> Lucene </input>
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
		$query_link = "http://localhost/incorrect_query.php?q=".$query."&algo=".$_GET['algo'];


?>
		<span style="font-size: 105%;"> Showing results for <a href=" <?php echo $spellcheck_link; ?>" style="text-decoration:none"> <i><?php echo htmlspecialchars($spellcheck_query, ENT_QUOTES, 'utf-8');  ?></i></a></span><br />
		<span style="font-size: 85%;"> Search instead for <a href=" <?php echo $query_link; ?>" style="text-decoration:none"> <?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8');  ?></a> <br /> </span> <br /> <?php
	}

?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>

<span>
<ol >
<?php

 foreach ($results->response->docs as $doc)
 {
		$snippet_text = "";
?>
      <li>
		<a href="<?php echo htmlspecialchars($doc->og_url,ENT_NOQUOTES,'utf-8'); ?>" style="text-decoration:none"> <?php echo htmlspecialchars($doc->title,ENT_NOQUOTES,'utf-8'); ?> </a><br>
		<a href="<?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?>"> <?php echo htmlspecialchars($doc->og_url, ENT_NOQUOTES, 'utf-8'); ?></a> <br>
<!--		ID: <?php echo htmlspecialchars($doc->id, ENT_NOQUOTES, 'utf-8'); ?> <br>-->
		<?php
	
			if($results->highlighting){

				$idd = $doc->id;
				$highlighting_array = (array) $results->highlighting->$idd;

		//		print_r($highlighting_array['description']);
				if(!array_key_exists('description', $highlighting_array))
					$snippet_text = $doc->description;
				else {
					$snippet_desc = implode(" ",$highlighting_array['description']);


					if(strpos(strtolower($snippet_desc), $spellcheck_query) !== false){
						$snippet_text =  $snippet_desc;
					}
					else{
						$query= trim($spellcheck_query);
						$q = explode(" ", $spellcheck_query);
						$valid = true;
						for($i = 0; $i < sizeof($q); ++$i)
						{
						     if(strpos(strtolower($snippet_desc), $q[$i]) == false){
							$valid = false;
							break;
						     }
						}

						if($valid && ($snippet_text=="" || $atleast_one_term)){
							$snippet_text =  $snippet_desc;
						}
						else{
							if(!$valid && $snippet_text==""){
								for($i = 0; $i < sizeof($q); ++$i)
								{
								     if(strpos(strtolower($snippet_desc), $q[$i]) == true){
									$atleast_one_term = true;
									$snippet_text =  $snippet_desc;
								     }
								}
							}
						}
					}
					if(substr($snippet_text, -1) != ".")
					{
						$snippet_text .= "...";
					}
					if(!ctype_upper($snippet_text[0]))
					{
						$temp = "...";
						$temp .= $snippet_text;
						$snippet_text = $temp;
					}
				}
				?>

				<div style="width:60%">
					<?php echo $snippet_text; ?>
				</div> <?php
			}
		?>
		<br/> <br>
      </li>
<?php
  }
?>
    </ol>
</span>
<?php
}

?>
  </body>
</html>
