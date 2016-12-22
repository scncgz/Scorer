<?php
use \NoahBuscher\Macaw\Macaw as Router;

Router::get('', 'Page@index');
Router::get('exam/(:any)', 'Page@exam');
Router::post('exam/(:any)/query', 'Page@query');