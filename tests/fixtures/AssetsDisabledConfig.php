<?php

return [
	"asset_1" => [], # enabled
	"asset_2" => [ "disabled" => false ], # enabled
	"asset_3" => [ "disabled" => true ], # disabled
	"asset_4" => [ "disable" => true ], # disabled
	"asset_5" => [ "disabled" => "foo" ], # enabled
	"asset_6" => [ "disable" => false ], # enabled
];
