<?php foreach( $items as $item ): ?>

<?php if( 'single' !== $item['template_type'] ):
	if( 'page' == $item['template_type'] ):
		$template = 'page.php';
	else:
		$template = $item['template_type'];
	endif
	?>
add_filter( 'template_include', '<?php echo $item['prefix'] ?>change_template' );
function <?php echo $item['prefix'] ?>change_template( $template ) {
	if( is_singular( '<?php echo $item['slug'] ?>' ) ) {
		return locate_template( <?php echo var_export( $template ) ?>, false, false );
	} else {
		return $template;
	}
}
<?php endif; ?>
<?php if( $item['before_content'] || $item['after_content'] ): ?>

add_filter( 'the_content', '<?php echo $item['prefix'] ?>add_fields_to_content' );
function <?php echo $item['prefix'] ?>add_fields_to_content( $content ) {
	if( ! is_singular( '<?php echo $item['slug'] ?>' ) ) {
		return $content;
	}

	<?php if( $item['before_content'] ): ?>$content = <?php echo var_export( trim( $item['before_content'] ) ) ?> . "\n\n" .$content;<?php endif ?>

	<?php if( $item['after_content'] ): ?>$content .= "\n\n" . <?php echo var_export( trim( $item['after_content'] ) ) ?>;<?php endif ?>

	return $content;
}<?php endif ?>
<?php endforeach ?>
