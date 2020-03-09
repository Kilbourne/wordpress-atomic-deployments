<?php
echo "Installing EWWW Tools".PHP_EOL;
exec("cd current && wp eval 'ewww_image_optimizer_install_tools();' ");