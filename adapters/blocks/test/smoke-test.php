<?php
/**
 * Smoke test of the block adapter against minimal WordPress stubs.
 * Run: npm run build:blocks && php adapters/blocks/test/smoke-test.php
 * It checks option sanitizing and the data-attributes produced per block.
 * It does not replace testing inside a real WordPress install.
 */

namespace {
	define( 'ABSPATH', '/' );
	define( 'AURORA_BLOCKS_PATH', dirname( __DIR__ ) . '/' );
	define( 'AURORA_BLOCKS_URL', '/p/' );
	define( 'AURORA_BLOCKS_VERSION', '0' );

	$GLOBALS['filters'] = [];
	function add_action() {}
	function add_filter() {}
	function apply_filters( $hook, $value ) {
		return isset( $GLOBALS['filters'][ $hook ] ) ? $GLOBALS['filters'][ $hook ]( $value ) : $value;
	}
	function wp_json_encode( $v ) {
		return json_encode( $v, JSON_UNESCAPED_SLASHES );
	}
	function wp_strip_all_tags( $s ) {
		return trim( strip_tags( $s ) );
	}

	require AURORA_BLOCKS_PATH . 'includes/class-schemas.php';
	require AURORA_BLOCKS_PATH . 'includes/class-block-targets.php';
	require AURORA_BLOCKS_PATH . 'includes/class-plugin.php';

	use Aurora\Blocks\Schemas;
	use Aurora\Blocks\Plugin;

	$failed = 0;
	function check( $label, $actual, $expected ) {
		global $failed;
		if ( $actual === $expected ) {
			echo "ok   $label\n";
			return;
		}
		$failed++;
		echo "FAIL $label\n     expected " . var_export( $expected, true ) . "\n     got      " . var_export( $actual, true ) . "\n";
	}

	if ( ! Schemas::available() ) {
		fwrite( STDERR, "includes/generated/schemas.json is missing; run `npm run build:blocks` first.\n" );
		exit( 1 );
	}

	foreach ( Plugin::MODULES as $module ) {
		check( "schema $module loaded", is_array( Schemas::get( $module ) ), true );
	}

	// Text on a heading: primary attribute, non-default options only.
	$r = Plugin::attributes_for( 'core/heading', [ 'text' => [ 'effect' => 'blur-reveal', 'duration' => 1200, 'delay' => 0 ] ] );
	check( 'heading text primary', $r['attributes']['data-aurora-text'] ?? null, 'blur-reveal' );
	check( 'heading text options', $r['attributes']['data-aurora-text-options'] ?? null, '{"duration":1200}' );
	check( 'heading modules', $r['modules'], [ 'text' ] );

	// A module turned on with nothing changed still renders its primary option.
	$r = Plugin::attributes_for( 'core/heading', [ 'text' => [] ] );
	check( 'empty options use the default effect', $r['attributes']['data-aurora-text'] ?? null, 'slide-in' );
	check( 'empty options add no -options attribute', isset( $r['attributes']['data-aurora-text-options'] ), false );

	// Block defaults: the button points the text module at its link.
	$r = Plugin::attributes_for( 'core/button', [ 'text' => [ 'effect' => 'blur-reveal' ] ] );
	check( 'button text target default', $r['attributes']['data-aurora-text-options'] ?? null, '{"target":".wp-block-button__link"}' );

	// Invalid values are dropped, numbers clamped, unknown options ignored.
	$r = Plugin::attributes_for( 'core/heading', [ 'text' => [ 'effect' => 'nope', 'duration' => 999999, 'bogus' => 1, 'split' => 'words' ] ] );
	check( 'invalid enum falls back to default', $r['attributes']['data-aurora-text'] ?? null, 'slide-in' );
	check( 'clamped and filtered options', $r['attributes']['data-aurora-text-options'] ?? null, '{"split":"words","duration":10000}' );

	// `when`: options hidden by their condition are left out.
	$r = Plugin::attributes_for( 'core/heading', [ 'text' => [ 'effect' => 'blur-reveal', 'trigger' => 'load', 'threshold' => 0.5 ] ] );
	check( 'option hidden by when is dropped', $r['attributes']['data-aurora-text-options'] ?? null, '{"trigger":"load"}' );

	// Tags are stripped from strings; bad colors are dropped.
	$r = Plugin::attributes_for( 'core/heading', [ 'text' => [ 'effect' => 'sparkles-text', 'sparkleColor' => 'red;}</style>', 'sparkleColor2' => '#00ff00' ] ] );
	check( 'invalid color dropped, valid kept', $r['attributes']['data-aurora-text-options'] ?? null, '{"sparkleColor2":"#00ff00"}' );
	$r = Plugin::attributes_for( 'core/group', [ 'children' => [ 'selector' => '<b>.x</b>' ] ] );
	check( 'tags stripped from selectors', $r['attributes']['data-aurora-children-options'] ?? null, '{"selector":".x"}' );

	// Modules are only applied on blocks that offer them.
	$r = Plugin::attributes_for( 'core/image', [ 'text' => [ 'effect' => 'blur-reveal' ] ] );
	check( 'module not offered on block', $r['attributes'], [] );
	$r = Plugin::attributes_for( 'core/heading', 'not-an-array' );
	check( 'non-array attribute ignored', $r['attributes'], [] );

	// Several modules on one block.
	$r = Plugin::attributes_for( 'core/cover', [ 'children' => [ 'animation' => 'zoom' ], 'gradient' => [ 'type' => 'mesh' ] ] );
	check( 'cover children root default', $r['attributes']['data-aurora-children-options'] ?? null, '{"root":".wp-block-cover__inner-container"}' );
	check( 'cover gradient primary', $r['attributes']['data-aurora-gradient'] ?? null, 'mesh' );
	check( 'cover modules', $r['modules'], [ 'children', 'gradient' ] );

	// Heading gradient defaults to painting the text.
	$r = Plugin::attributes_for( 'core/heading', [ 'gradient' => [ 'type' => 'linear' ] ] );
	check( 'heading gradient paints text', $r['attributes']['data-aurora-gradient-options'] ?? null, '{"target":"text"}' );

	// The aurora_blocks_targets filter can offer a module on another block.
	$GLOBALS['filters']['aurora_blocks_targets'] = function ( $t ) {
		$t['text']['my/title'] = [ 'target' => '.t' ];
		return $t;
	};
	$reflection = new ReflectionProperty( Aurora\Blocks\Block_Targets::class, 'targets' );
	$reflection->setAccessible( true );
	$reflection->setValue( null, null );
	$r = Plugin::attributes_for( 'my/title', [ 'text' => [] ] );
	check( 'filter adds a block', $r['attributes']['data-aurora-text-options'] ?? null, '{"target":".t"}' );

	echo $failed ? "\n$failed failed\n" : "\nAll passed\n";
	exit( $failed ? 1 : 0 );
}
