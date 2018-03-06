<?php

return [
	"asset_1" => [
		"limit" => [
			":Sample:Page:.*"
		],
		"css" => [
			__DIR__ . "/dummy_3.css",
		]
	],
	"asset_2" => [
		"limit" => [
			"*"
		],
		"css" => [
			__DIR__ . "/dummy_1.css",
			__DIR__ . "/dummy_2.css"
		]
	],
	"asset_3" => [
		"css" => [
			__DIR__ . "/dummy_4.css",
		]
	],
	"asset_4" => [
		"limit" => [
			"*",
			"!:Sample:Page:.*"
		],
		"css" => [
			__DIR__ . "/dummy_5.css",
		]
	]
];