<?php

return [
	"asset_1" => [
		"order" => [ "after" => "asset_2" ],
	],
	"asset_2" => [],
	"asset_3" => [
		"order" => [
			"type" => "start"
		]
	],
	"asset_4" => [
		"order" => "end"
	],
	"asset_5" => [
		"order" => [
			"type" => "before",
			"position" => "asset_2"
		]
	],
	"asset_6" => [
		"order" => "start"
	],
];
