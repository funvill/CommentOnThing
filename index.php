<?php 
/**
 * SINGLE FILE thing comment system. 
 *
 * Tables:
 * -------------------------
 * Things
 *   slug - URI slug 
 *   name - Title of the thing
 *   body - Contents of the page 
 * 
 */ 

define("SQLITE_DATABASE",  	'data.sqlite');
define("DISQUS_SHORTNAME", 	't-abluestar-com');
define("GOOGLE_MAP_API", 		'AIzaSyAWNELaAjfGdi6nieLuvcGt-693M-K9d0Q');



class MyThing extends SQLite3
{
    function __construct()
    {
        $this->open( SQLITE_DATABASE );
        
        // Ensure that the database has the correct table. 
	    	$st=$this->prepare( 'CREATE TABLE IF NOT EXISTS things (slug STRING PRIMARY KEY, name STRING, body STRING, address STRING)' );	
	    	$st->execute( );
    }

    function GetBySlug( $slug ) {

    	$st=$this->prepare('SELECT * FROM things WHERE slug=?');			
			$st->bindParam(1, $slug, SQLITE3_TEXT);
			$result = $st->execute( );
    	if( $result == false ) {
    		return false; 
    	}
			return $result->fetchArray( SQLITE3_ASSOC ) ;
    }

    function GetAll( ) {
    	$st=$this->prepare('SELECT * FROM things');	
    	$result = $st->execute( );

    	// Loop thought the results. 
			$results = array() ; 
			while( $row = $result->fetchArray( SQLITE3_ASSOC ) ) {
				$results[] = $row ; 
			}
			return $results; 
    }

    function Update( $slug, $name, $body, $address ) {
    	// http://stackoverflow.com/questions/15277373/sqlite-upsert-update-or-insert

    	// Do this in a safe way 
    	$st=$this->prepare('UPDATE things SET name=?, body=?, address=? WHERE slug=? ;');			
			$st->bindParam(1, $name, SQLITE3_TEXT);
			$st->bindParam(2, $body, SQLITE3_TEXT);
			$st->bindParam(3, $address, SQLITE3_TEXT);
			$st->bindParam(4, $slug, SQLITE3_TEXT);
			$st->execute(); 

    	$st=$this->prepare('INSERT OR IGNORE INTO things (slug, name, body, address) VALUES (?, ?, ?, ?); ');
			$st->bindParam(1, $slug, SQLITE3_TEXT);
			$st->bindParam(2, $name, SQLITE3_TEXT);
			$st->bindParam(3, $body, SQLITE3_TEXT);
			$st->bindParam(4, $address, SQLITE3_TEXT);
			$st->execute(); 
    }
}


// Display logic 
// --------------------------------
// Default, List all pages 
$page['act']  	= 'list'; 
$page['title']  = ''; 
$page['slug']		= '';

// Check to see if ACT is set
if( isset($_REQUEST['act'] ) ) {
	// Check to make sure that its a valid act 
	if( in_array( $_REQUEST['act'], array('edit', 'view', 'list', 'update') ) ) {
		$page['act'] = $_REQUEST['act'] ; 
	} else {
		echo 'Error (1): Unknow act=['. $_REQUEST['act'] .']';
	}
} 

// Check to see if we have defined a slug. 
if( isset($_REQUEST['thing'] ) ) {
	$page['slug'] = $_REQUEST['thing'] ;
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
		$page['title'] = 'List all' ; 
		break ; 
	}
	case 'edit':
	{
		$page['data'] = $db->GetBySlug( $page['slug'] ) ;
		$page['title'] = 'Edit '. $page['slug'] ;
		break; 
	} 
	case 'update': 
	{
		if( ! isset($_REQUEST['name'] ) || ! isset($_REQUEST['body'] ) /* || ! isset($_REQUEST['address'] ) */ ) {
			echo "Error: Missing required prameters" ; 
			die(); 
		}

		// Update the database 
		$db->Update( $page['slug'], $_REQUEST['name'], $_REQUEST['body'], /*$_REQUEST['address']*/ "" ); 

		// Redirect to the view page. 
		header('Location: http://'. $_SERVER['HTTP_HOST'] .'/?thing='. $page['slug'] .'&act=view');
		exit;		
	}
}


// echo '<pre>'; var_dump( $page ) ; echo '</pre>'; 


// This should all be put in to a template file 
// But I wanted this to be as simple as possiable. 
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
          <h1>{Project Name}</h1>
          <a href='/'>List all </a>
        </div>
      </div>
    </nav>

    <!-- Main content -->
<?php 
// Only show the comment thread on the single page thread. 
if( $page['act'] == 'view' ) { ?>	

		<div class="container">
			<h2><?php echo $page['data']['name'] ; ?></h2>
			<p><?php echo $page['data']['body'] ; ?></p>

			<?php 
			/*
			// Display the map if needed. 
			if( strlen( $page['data']['address'] ) > 0 ) { ?>
			<iframe width="600" height="450" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q=<?php echo urlencode( $page['data']['address'] ) ; ?>&key=<?php echo GOOGLE_MAP_API ;?>"></iframe>
			<?php } 
			*/ ?>

			<!-- disqus thread.-->
			<div id="disqus_thread"></div>
		</div>

<?php } else if ( $page['act'] == 'list' ) {

		echo '<div class="container">';
		echo '<ul>'; 
		foreach( $page['data'] as $thing ) {
			echo '<li><a href="/?thing='. $thing['slug'] .'&act=view">'. $thing['name'] .'</a></li>'; 
		}
		echo '</ul>'; 
		echo '</div>';

} else if ( $page['act'] == 'edit' ) { ?>

		<form action='/?act=update' method='POST'>
			<!-- <label for='slug'>Slug:</label><br />--><input id='slug' type='hidden' name='thing' value='<?php echo $page['slug'] ; ?>' readonly /> <!-- Read only<br /> -->
			<label for='name'>Name:</label><br /><input id='name' type='text' name='name' value='<?php echo $page['data']['name'] ; ?>' /><br />
			<!-- <label for='name'>Address:</label><br /><input id='name' type='text' name='address' value='<?php echo $page['data']['address'] ; ?>' /><br /> -->
			<label for='body'>Body:</label><br /><textarea id='body' name='body'><?php echo $page['data']['body'] ; ?></textarea><br />
			<input type='submit'>
		</form>

<?php } ?>

		<footer>
<?php if( $page['act'] == 'view' ) { ?>	

			<script type="text/javascript">
		    /* * * CONFIGURATION VARIABLES * * */
		    var disqus_shortname  = '<?php echo DISQUS_SHORTNAME ; ?>';
		    var disqus_identifier = '<?php echo $page['data']['slug'] ; ?>';
		    
		    /*** DON'T EDIT BELOW THIS LINE ***/
		    (function() {
		        var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
		        dsq.src = '//' + disqus_shortname + '.disqus.com/embed.js';
		        (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
		    }());
			</script>
<?php } ?>

		</footer>	
</body>
</html>