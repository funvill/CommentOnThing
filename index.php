<?php 
/**
 * SINGLE FILE thing comment system. 
 *
 * ToDo: 
 * - Add valadation on the slug input.  
 * - Add a forum to Add and Edit things.
 *
 * Tables:
 * -------------------------
 *
 * Things
 *   slug - URI slug 
 *   name - Title of the thing
 *   body - Contents of the page 
 *
 * 
 */ 


class MyThing extends SQLite3
{
    function __construct()
    {
        $this->open('data.db');
        
        // Ensure that the database has the correct table. 
        $this->exec( 'CREATE TABLE IF NOT EXISTS things (slug STRING PRIMARY KEY, name STRING, body STRING)' );
        
        // Debug insert a new thing 
        /*
        $this->Add("cat", "cat", "This is the cat");
        $this->Add("dog", "dog", "This is the dog");
        $this->Add("fox", "fox", "This is the fox");
        */
    }

    function exec( $sql ) {
    	// echo '<pre>'.$sql."\n</pre>"; // Debug 
    	return parent::exec( $sql );
    }

    function GetBySlug( $slug ) {
			$result = $this->query( 'SELECT * FROM things WHERE slug="'. $slug .'"' );
			return $result->fetchArray( SQLITE3_ASSOC ) ;
    }

    function GetAll( ) {
    	$result = $this->query( 'SELECT * FROM things' );
			$results = array() ; 
			while( $row = $result->fetchArray( SQLITE3_ASSOC ) ) {
				$results[] = $row ; 
			}
			return $results; 
    }

    function Add( $slug, $name, $body ) {

    	// Do this in a safe way 
    	$st=$this->prepare('INSERT INTO things (slug, name, body) VALUES (?, ?, ?); ');
			$st->bindParam(1, $slug, SQLITE3_TEXT);
			$st->bindParam(2, $name, SQLITE3_TEXT);
			$st->bindParam(3, $body, SQLITE3_TEXT);
			return $st->execute(); 
    }
}

// If we have slug defined then show that single page. 
// If we don't have a slug defined then list all the pages. 

// Default, List all pages 
$page['act']  = 'list'; 
$page['slug'] = '';  

// Check to see if we have defined a slug. 
if( isset($_REQUEST['slug'] ) ) {
	$page['act']  = 'view'; 
	$page['slug'] = $_REQUEST['slug'] ; 
} 

// Connect to the MyThings database. 
$db = new MyThing();

// Get the data depending on the act. 
switch( $page['act'] ) {
	case 'view': {
		$page['data'] = $db->GetBySlug( $page['slug'] ) ;

		$page['title'] = $page['data']['name'] ; 
		break; 

	}
	case 'list': {
		$page['data'] = $db->GetAll( ) ;
		$page['title'] = 'list all' ; 
		break ; 
	}
}


// echo '<pre>'; var_dump( $page ) ; echo '<pre>'; 

?><!doctype html>
<html class="no-js" lang="">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo $page['title'] ; ?> - Comment on this thing</title>
    <meta name="description" content="A page where you can comment on things">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
  <body>
    <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->

    <!-- Top nav -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <h1>Comment on a thing</h1>
          <a href='/'>List all </a>
        </div>
      </div>
    </nav>

    <!-- Main content -->
 		<?php 
			// Only show the comment thread on the single page thread. 
			if( $page['act'] == 'view' ) { 
			?>
	
		<div class="container">
			<h2><?php echo $page['data']['name'] ; ?></h2>
			<p><?php echo $page['data']['body'] ; ?></p>

			<!-- disqus thread.-->
			<div id="disqus_thread"></div>
		</div>

		<footer>
			<script type="text/javascript">
		    /* * * CONFIGURATION VARIABLES * * */
		    var disqus_shortname = 't-abluestar-com';
		    var disqus_identifier = '<?php echo $page['data']['name'] ; ?>';
		    
		    /*** DON'T EDIT BELOW THIS LINE ***/
		    (function() {
		        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
		        dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
		        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
		    }());
			</script>
		</footer>

	<?php } else if ( $page['act'] == 'list' ) {

		echo '<div class="container">';
		echo '<ul>'; 
		foreach( $page['data'] as $thing ) {
			echo '<li><a href="/?slug='. $thing['slug'] .'">'. $thing['name'] .'</a></li>'; 
		}
		echo '</ul>'; 
		echo '</div>';
	} ?>
</body>
</html>