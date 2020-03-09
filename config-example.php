<?php

return [
	/* 
		Here set the symlinks shared on each release
		The key of array sets the path of the symlink relative to the root of the project ( 'root' equals to an empty string ).
		In the array put the path of the symlink target relative to shared folder.
	*/
	'shared'=> [
		'root' => [
			'.env',
		],
		'public/wp-content/' =>[
			'languages',
			'sedlex',
			'uploads',
		],
		'public/wp-content/plugins/' => [
			'responsive-menu-pro-data'
		]
	],
	/* How many release to preserve */
	'preserve_release'=> 3,
];