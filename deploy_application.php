<?php 

function removeFolderRecursively($path) {
    $dir = opendir($path);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $path . '/' . $file;
            if ( is_dir($full) ) {
                removeFolderRecursively($full);
            }
            else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($path);
}

function execAndMaybeExit($command){
    exec($command, $output, $return);
    if($return !== 0){
        exit(1);
    }
}

ob_implicit_flush(1);
$DEPLOYMENT_ID = time();
$options = include(__DIR__.'/config.php') ?: [];
$preserve_release = isset($options['preserve_release']) && ($preserve_release = (int) $options['preserve_release']) > 0 ?  $preserve_release : 3;

echo "Creating: releases/$DEPLOYMENT_ID".PHP_EOL;
execAndMaybeExit("cp -r deploy_cache/ releases/$DEPLOYMENT_ID");
echo "Linking shared to release: $DEPLOYMENT_ID".PHP_EOL;
$cwd = getcwd() . DIRECTORY_SEPARATOR;
$shared_folder = $cwd . 'shared' . DIRECTORY_SEPARATOR;
$release_folder = $cwd.'releases'. DIRECTORY_SEPARATOR. $DEPLOYMENT_ID . DIRECTORY_SEPARATOR;
if(isset($options['shared'])){
    foreach ($options['shared'] as $path => $filesArray) {
    	foreach($filesArray as $file){
    		$src = $shared_folder.$file;
    		$target = $release_folder . ($path === 'root' ? '' : $path ) . $file;
    		if(!file_exists($src)){
                echo "Missing file: $src".PHP_EOL;
                exit(1);
            }
    		if( file_exists($target) ){
    			if(is_dir($target)){
    				removeFolderRecursively($target);
    			}else{
    				unlink($target);
    			}
    		}
            execAndMaybeExit("ln -s $src $target");
    	}
    }
}

echo "Resetting file permission".PHP_EOL;
exec("find . -type f -print0 | xargs -0 chmod 664");
exec("find . -type d -print0 | xargs -0 chmod 775");
echo "Switching current symlink".PHP_EOL;    
execAndMaybeExit("rm -rf current");
execAndMaybeExit("ln -s releases/$DEPLOYMENT_ID current");
echo "Removing old releases".PHP_EOL;
exec("cd releases && ls -t | tail -n +".(1+$preserve_release)." | xargs rm -rf");
if(file_exists('post_activation.php')){
    echo "Post Activation Hook".PHP_EOL;
    include('post_activation.php');
}
