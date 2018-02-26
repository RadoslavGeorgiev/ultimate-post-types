add_action( 'init', '<?php echo $function_name ?>' );
function <?php echo $function_name ?>() {
	<?php
	$i = 0;
	foreach( $pairs as $pair ) {
		if( 1 === count( $pair[ 1 ] ) ) {
			$pt_arg = "'" . addslashes( $pair[ 1 ][ 0 ] ) . "'";
		} else {
			$slahed = array_map( 'addslashes', $pair[ 1 ] );
			$pt_arg = "array( '" . implode( $slahed, "', '" ) . "' )";
		}

		printf(
			"register_taxonomy( '%s', %s, %s );",
			addslashes( $pair[ 0 ] ),
			$pt_arg,
			$pair[ 2 ]
		);

		if( $i < count( $pairs ) - 1 ) {
			echo "\n\n\t";
		}

		$i++;
	}
	echo "\n";
	?>
}

