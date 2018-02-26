add_action( 'init', '<?php echo $function_name ?>' );
function <?php echo $function_name ?>() {
	<?php
	$i = 0;
	foreach( $pairs as $pair ) {
		printf(
			"register_post_type( '%s', %s );",
			addslashes( $pair[ 0 ] ),
			$pair[ 1 ]
		);

		if( $i < count( $pairs ) - 1 ) {
			echo "\n\n\t";
		}

		$i++;
	}
	echo "\n";
	?>
}

