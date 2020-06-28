<?php 

function execAndMaybeExit($command){
    exec($command, $output, $return);
    if($return !== 0){
        exit(1);
    }
}

ob_implicit_flush(1);
$DEPLOYMENT_ID = time();
$cwd = getcwd() . DIRECTORY_SEPARATOR;
echo "Creating: releases/$DEPLOYMENT_ID".PHP_EOL;
execAndMaybeExit("cp -r deploy_cache/ releases/$DEPLOYMENT_ID");
if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'releases'. DIRECTORY_SEPARATOR. $DEPLOYMENT_ID . DIRECTORY_SEPARATOR.'deploy_config.php')){
    $options = include(__DIR__.DIRECTORY_SEPARATOR.'releases'. DIRECTORY_SEPARATOR. $DEPLOYMENT_ID . DIRECTORY_SEPARATOR.'deploy_config.php');
    if(!is_array($options)) $options = [];
}else{
    $options = [];
}
$preserve_release = isset($options['preserve_release']) && ($preserve_release = (int) $options['preserve_release']) > 0 ?  $preserve_release : 3;

if(isset($options['shared'])){
    echo "Linking shared to release: $DEPLOYMENT_ID".PHP_EOL;
    $shared_folder = $cwd . 'shared' . DIRECTORY_SEPARATOR;
    $release_folder = $cwd.'releases'. DIRECTORY_SEPARATOR. $DEPLOYMENT_ID . DIRECTORY_SEPARATOR;
    foreach ($options['shared'] as $path => $filesArray) {
    	foreach($filesArray as $file){
    		$src = $shared_folder.$file;
    		$target = $release_folder . ($path === 'root' ? '' : $path ) . $file;
    		if(!file_exists($src)){
                echo "Missing file: $src".PHP_EOL;
                exit(1);
            }
    		if( file_exists($target) ){
                execAndMaybeExit("rm -rf $target");
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
if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'current'. DIRECTORY_SEPARATOR.'deploy_post_activation.php')){
    echo "Post Activation Hook".PHP_EOL;
    include(__DIR__.DIRECTORY_SEPARATOR.'current'. DIRECTORY_SEPARATOR.'deploy_post_activation.php');
}
