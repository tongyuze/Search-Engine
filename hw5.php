<?php
// make sure browsers see this page as utf-8 encoded HTML
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
$results = false;
$method = $_GET["method"];
$corr = $_GET["corr"];
if($query)
{
 $old_query = $query;
 $is_spellcorrect = false;
 $sc = new SpellCorrector();
 $words = explode(" ", $query); 
 for($j = 0; $j < sizeof($words); $j++) {
  $correct_query = $correct_query." ".$sc->correct($words[$j]);
 }
 if(trim(strtolower($correct_query)) != trim(strtolower($query)) && $corr!="no") {
  $query = $correct_query;
  $is_spellcorrect = true;
 }
 // The Apache Solr Client library should be on the include path
 // which is usually most easily accomplished by placing in the
 // same directory as this script ( . or current directory is a default
 // php include path entry in the php.ini)
 require_once('Apache/Solr/Service.php');
 // create a new solr service instance - host, port, and corename
 // path (all defaults in this example)
 $solr = new Apache_Solr_Service('localhost', 8983, '/solr/myexample/');
 // if magic quotes is enabled then stripslashes will be needed
 if (get_magic_quotes_gpc() == 1)
 {
 $query = stripslashes($query);
 }
 // in production code you'll always want to use a try /catch for any
 // possible exceptions emitted by searching (i.e. connection
 // problems or a query parsing error)
 $Parameter = array('fl' => 'title, og_url, id, description');
 $additionalParameter = array('sort' => 'pageRankFile desc', 'fl' => 'title, og_url, id, description');
 try
 {
  if($method == "Lucene") {
    $results = $solr->search($query, 0, $limit, $Parameter);
  }
  else{
     $results = $solr->search($query, 0, $limit, $additionalParameter);
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
    <title>CSCI572_HW5</title>
  <link rel="stylesheet" href="//apps.bdimg.com/libs/jqueryui/1.10.4/css/jquery-ui.min.css">
  <script src="//apps.bdimg.com/libs/jquery/1.10.2/jquery.min.js"></script>
  <script src="//apps.bdimg.com/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>

  </head>
  <body>
    <div class="ui-widget">
    <form  accept-charset="utf-8" method="get">
      <label for="q">Search:</label>
      <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($old_query, ENT_QUOTES, 'utf-8'); ?>"/>
      <select name = "method">
        <option value="Lucene" <?php if($method==="Lucene") {echo 'selected="selected"';} ?>>Lucene</option>
        <option value="PageRank" <?php if($method==="PageRank") {echo 'selected="selected"';} ?>>PageRank</option>
      </select>
      <input type="submit" value="submit" />
    </form>
  </div>



<script>
  $('#q').bind('input propertychange', function() {
  var qu = $("#q").val().toLowerCase();
  var url = "http://localhost:8983/solr/myexample/suggest?q=" + qu + "&wt=json&json.wrf=callback=getJSON";
  var recomand =[];
  $.ajax({
    url : url,
    jsonpCallback : 'getJSON',
    contentType : "application/json",
    dataType : 'jsonp',              
    success : function(data) {
      var prefix = data.suggest.suggest[qu].suggestions;
      for(i in prefix) {
        //alert(JSON.stringify(prefix[i].term));
        recomand.push(prefix[i].term);
      }
      //alert(recomand);
      $("#q").autocomplete({
        source: recomand
      });
    },
    error: function(e) {
      alert(e);
    }
    });
  }); 
</script>


<?php
if($is_spellcorrect) {
  echo "<font size=5>Showing results for ".$correct_query.'</font><br>';
  echo "Search instead of ".'<a href=test.php?q='.$old_query.'&method='.$method.'&corr=no'.'>'.$old_query.'</a><br><br>';
}

// display results
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
?>
    <div>Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</div>
    <ol>
<?php
  $csvf = fopen("UrlToHtml_foxnews.csv", "r");
  while($content = fgetcsv($csvf)){
    $mapfile[$content[0]] = $content[1];
  }

  // iterate result documents
  foreach ($results->response->docs as $doc)
  {
?>
      <li>
        <table style="border: 1px solid black; text-align: left">
          <tr>
            <th>Title</th>
            <td><a href=<?php if($doc -> og_url==null){echo $mapfile[$doc -> id];} else{echo $doc -> og_url;}?>> <?php echo htmlspecialchars($doc -> title, ENT_NOQUOTES, 'utf-8');?><a/></td>
          </tr>

          <tr>
            <th>URL</th>
            <td><a href=<?php if($doc -> og_url==null){echo $mapfile[$doc -> og_url];} else{echo $doc -> og_url;} ?>> <?php echo htmlspecialchars($doc -> og_url, ENT_NOQUOTES, 'utf-8');?><a/></td>
          </tr>

          <tr>
            <th>ID</th>
            <td><?php echo htmlspecialchars(substr($doc -> id, 70), ENT_NOQUOTES, 'utf-8'); ?></td>
          </tr>

          <tr>
            <th>Description</th>
            <td><?php if($doc -> description != null){echo htmlspecialchars($doc -> description, ENT_NOQUOTES, 'utf-8');}else{echo "NA";} ?></td>
          </tr>
          <?php 
          $html_file = file_get_contents($doc -> og_url);
          $aim = null;
          $match = $query;

   
          $aim = stripos($html_file, trim($match));
          while($aim == null) {
            $pieces = explode(" ", $match);
            $match = str_replace($pieces[sizeof($pieces)-1], "", $match);
            $match = trim($match);
            echo $match;
            $aim = stripos($html_file, trim($match));
            if(sizeof(explode(" ", $match)) == 1) {
              break;
            }
          }
          if($aim != null) {
            $stop = min(stripos($html_file, ".", $aim), stripos($html_file, "<", $aim), stripos($html_file, "\"", $aim));
            $snippet_head = substr($html_file, 0, $stop);//$start." ".$stop;
            $start = max(strripos($snippet_head, "."), strripos($snippet_head, ">"), strripos($snippet_head, "\""));
            $snippet = substr($snippet_head, $start+1);
          }
          if($snippet != null) {
            echo "<tr>";
            echo "<th>Snippets</th>";
            echo "<td>".$snippet."</td>";
            echo "</tr>";
          }
          ?>
        </table>
      </li>
<?php
  }
?>
    </ol>
<?php
}
?>
  </body>
</html>

<?php
/*
*************************************************************************** 
*   Copyright (C) 2008 by Felipe Ribeiro                                  * 
*   felipernb@gmail.com                                                   * 
*   http://www.feliperibeiro.com                                          * 
*                                                                         * 
*   Permission is hereby granted, free of charge, to any person obtaining * 
*   a copy of this software and associated documentation files (the       * 
*   "Software"), to deal in the Software without restriction, including   * 
*   without limitation the rights to use, copy, modify, merge, publish,   * 
*   distribute, sublicense, and/or sell copies of the Software, and to    * 
*   permit persons to whom the Software is furnished to do so, subject to * 
*   the following conditions:                                             * 
*                                                                         * 
*   The above copyright notice and this permission notice shall be        * 
*   included in all copies or substantial portions of the Software.       * 
*                                                                         * 
*   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,       * 
*   EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF    * 
*   MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.* 
*   IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR     * 
*   OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, * 
*   ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR * 
*   OTHER DEALINGS IN THE SOFTWARE.                                       * 
*************************************************************************** 
*/ 


/**
 * This class implements the Spell correcting feature, useful for the 
 * "Did you mean" functionality on the search engine. Using a dicionary of words
 * extracted from the product catalog.
 * 
 * Based on the concepts of Peter Norvig: http://norvig.com/spell-correct.html
 * 
 * @author Felipe Ribeiro <felipernb@gmail.com>
 * @date September 18th, 2008
 * @package catalog
 *
 */
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 60);
class SpellCorrector {
  private static $NWORDS;
  
  /**
   * Reads a text and extracts the list of words
   *
   * @param string $text
   * @return array The list of words
   */
  private static function  words($text) {
    $matches = array();
    preg_match_all("/[a-z]+/",strtolower($text),$matches);
    return $matches[0];
  }
  
  /**
   * Creates a table (dictionary) where the word is the key and the value is it's relevance 
   * in the text (the number of times it appear)
   *
   * @param array $features
   * @return array
   */
  private static function train(array $features) {
    $model = array();
    $count = count($features);
    for($i = 0; $i<$count; $i++) {
      $f = $features[$i];
      if(array_key_exists($f, $model)) {
        $model[$f] += 1;
      }
      else {
        $model[$f] = 1;
      }
    }
    return $model;
  }
  
  /**
   * Generates a list of possible "disturbances" on the passed string
   *
   * @param string $word
   * @return array
   */
  private static function edits1($word) {
    $alphabet = 'abcdefghijklmnopqrstuvwxyz';
    $alphabet = str_split($alphabet);
    $n = strlen($word);
    $edits = array();
    for($i = 0 ; $i<$n;$i++) {
      $edits[] = substr($word,0,$i).substr($word,$i+1);     //deleting one char
      foreach($alphabet as $c) {
        $edits[] = substr($word,0,$i) . $c . substr($word,$i+1); //substituting one char
      }
    }
    for($i = 0; $i < $n-1; $i++) {
      $edits[] = substr($word,0,$i).$word[$i+1].$word[$i].substr($word,$i+2); //swapping chars order
    }
    for($i=0; $i < $n+1; $i++) {
      foreach($alphabet as $c) {
        $edits[] = substr($word,0,$i).$c.substr($word,$i); //inserting one char
      }
    }

    return $edits;
  }
  
  /**
   * Generate possible "disturbances" in a second level that exist on the dictionary
   *
   * @param string $word
   * @return array
   */
  private static function known_edits2($word) {
    $known = array();
    foreach(self::edits1($word) as $e1) {
      foreach(self::edits1($e1) as $e2) {
        if(array_key_exists($e2,self::$NWORDS)) $known[] = $e2;       
      }
    }
    return $known;
  }
  
  /**
   * Given a list of words, returns the subset that is present on the dictionary
   *
   * @param array $words
   * @return array
   */
  private static function known(array $words) {
    $known = array();
    foreach($words as $w) {
      if(array_key_exists($w,self::$NWORDS)) {
        $known[] = $w;

      }
    }
    return $known;
  }
  
  
  /**
   * Returns the word that is present on the dictionary that is the most similar (and the most relevant) to the
   * word passed as parameter, 
   *
   * @param string $word
   * @return string
   */
  public static function correct($word) {
    $word = trim($word);
    if(empty($word)) return;
    
    $word = strtolower($word);
    
    if(empty(self::$NWORDS)) {
      
      /* To optimize performance, the serialized dictionary can be saved on a file
      instead of parsing every single execution */
      if(!file_exists('serialized_dictionary.txt')) {
        self::$NWORDS = self::train(self::words(file_get_contents("big.txt")));
        $fp = fopen("serialized_dictionary.txt","w+");
        fwrite($fp,serialize(self::$NWORDS));
        fclose($fp);
      } else {
        self::$NWORDS = unserialize(file_get_contents("serialized_dictionary.txt"));
      }
    }
    $candidates = array(); 
    if(self::known(array($word))) {
      return $word;
    } elseif(($tmp_candidates = self::known(self::edits1($word)))) {
      foreach($tmp_candidates as $candidate) {
        $candidates[] = $candidate;
      }
    } elseif(($tmp_candidates = self::known_edits2($word))) {
      foreach($tmp_candidates as $candidate) {
        $candidates[] = $candidate;
      }
    } else {
      return $word;
    }
    $max = 0;
    foreach($candidates as $c) {
      $value = self::$NWORDS[$c];
      if( $value > $max) {
        $max = $value;
        $word = $c;
      }
    }
    return $word;
  }
  
  
}

?>