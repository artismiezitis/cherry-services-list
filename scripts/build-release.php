<?php
/** Build a complete installable WordPress ZIP, including the patched framework. */
if ( PHP_SAPI !== 'cli' ) {
	exit( "Run this script from the command line.\n" );
}
if ( ! class_exists( 'ZipArchive' ) ) {
	fwrite( STDERR, "Enable the PHP zip extension first.\n" );
	exit( 1 );
}
$root = dirname( __DIR__ );
$header = file_get_contents( $root . '/cherry-services-list.php' );
preg_match( '/^ \* Version:\s*(\S+)/m', $header, $match );
$version = $match[1] ?? '';
if ( ! preg_match( '/^[a-zA-Z0-9.-]+$/D', $version ) ) {
	exit( "Invalid plugin version.\n" );
}
$destination = $argv[1] ?? $root . '/dist';
if ( ! is_dir( $destination ) && ! mkdir( $destination, 0775, true ) ) {
	exit( "Cannot create release directory.\n" );
}
$filename = $destination . '/cherry-services-list-' . $version . '.zip';
$zip = new ZipArchive();
if ( true !== $zip->open( $filename, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
	exit( "Cannot create release ZIP.\n" );
}
$files = array( 'cherry-services-list.php', 'index.php', 'readme.txt', 'wpml-config.xml', 'LICENSE' );
foreach ( array( 'admin', 'public', 'templates', 'cherry-framework' ) as $directory ) {
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $directory, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $iterator as $file ) {
		$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
		if ( ! $file->isFile() || preg_match( '~(^|/)\.[^/]*~', $relative ) ) {
			continue;
		}
		$files[] = $relative;
	}
}
sort( $files );
foreach ( $files as $file ) {
	if ( ! is_file( $root . '/' . $file ) || ! $zip->addFile( $root . '/' . $file, 'cherry-services-list/' . $file ) ) {
		throw new RuntimeException( 'Cannot package ' . $file );
	}
}
$zip->close();
file_put_contents( $filename . '.sha256', hash_file( 'sha256', $filename ) . '  ' . basename( $filename ) . PHP_EOL );
echo $filename . PHP_EOL;
