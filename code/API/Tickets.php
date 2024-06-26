<?php

namespace API;
$categories = [
    [
        'id' => 1,
        'name' => 'Category1',
        'parent' => null,
        'childs' => [
            [
                'id' => 3,
                'name' => 'Category3',
                'parent' => 1,
                'childs' => [
                    [
                        'id' => 5,
                        'name' => 'Category5',
                        'parent' => 3,
                        'childs' => []
                    ],
                    [
                        'id' => 5,
                        'name' => 'Category5',
                        'parent' => 3,
                        'childs' => []
                    ],
                ]
            ],
            [
                'id' => 4,
                'name' => 'Category4',
                'parent' => 1,
                'childs' => [
                    [
                        'id' => 6,
                        'name' => 'Category6',
                        'parent' => 4,
                        'childs' => [
                            [
                                'id' => 7,
                                'name' => 'Category7',
                                'parent' => 6,
                                'childs' => []
                            ],
                        ]
                    ],
                ]
            ],
        ]
    ],
    [
        'id' => 2,
        'name' => 'Category2',
        'parent' => null,
        'childs' => [
            [
                'id' => 8,
                'name' => 'Category8',
                'parent' => 2,
                'childs' => [
                    [
                        'id' => 9,
                        'name' => 'Category9',
                        'parent' => 8,
                        'childs' => []
                    ],
                ]
            ],
            [
                'id' => 10,
                'name' => 'Category10',
                'parent' => 2,
                'childs' => []
            ],
        ]
    ]
];
class Tasks
{
    public function test($strings)
    {
        $prefix = [];
        foreach ($strings as $string) {
            $original_string = $string;
            foreach ($strings as $str) {

                $string = $original_string;
                if ($string == $str){
                    continue;
                }
                for ($i = strlen($string); $i >= 0; $i--) {
                    if (!(substr($str, 0, strlen($string)) === $string)) {
                        $string = substr($string, 0, -1);
                    } else {
                        if($string > $prefix || $string != $original_string){
                            array_push($prefix, $string);
                        }
                    }
                }
            }

        }

        $freqArray = array_count_values($prefix);
        $mostCom = max($freqArray);
        $mostFreq = array_search($mostCom, $freqArray);

        return $mostFreq;
    }

    public function getUsers($users, $colloborators) {
        $output_users = [];
        foreach ($colloborators as $colloborator) {
            $found = array_search($colloborator, array_column($users, 'id'));
            if ($found !== false) {
                $user = $users[$found];
                array_push($output_users, $user);
            }


        }
        return $output_users;
    }

    public function upgradeCategory(&$categories) {
        $topLevelCategories = [];

        function arrayWalk(&$array, &$topLevelCategories, $currentParent = 0) {
            foreach ($array as $value) {
                if(false === isset($value['parent'])) {
                    $currentParent = $value['id'];
                } else {
                    $value['parent'] = $currentParent;
                }

                $topLevelCategories[] = $value;
                arrayWalk($value['childs'], $topLevelCategories, $currentParent);
                unset($value['childs']);
            }
        }


        arrayWalk($categories, $topLevelCategories);

        $reconstructedCategories = [];
        foreach ($topLevelCategories as $category) {
            if(false === isset($category['parent'])) {
                $reconstructedCategories[$category['id']] = $category;
                //array_push($reconstructedCategories[$category['id']], $category);
                continue;
            }
            $reconstructedCategories[$category['parent']]['childs'][] = $category;
            //array_push($reconstructedCategories[$category['parent']]['childs'][], $category);

        }

        return $reconstructedCategories;
    }
}

$task = new Tasks();



$result = $task->upgradeCategory($categories);
var_dump($result);

//$result = $task->test(["flower", "test", "flow","flight"]);

$users = [
    [
        'id'    => 17,
        'email' => 'test17@test.com',
        'name'  => 'test17',
        'phone' => '17'
    ],
    [
        'id'    => 67,
        'email' => 'test67@test.com',
        'name'  => 'test67',
        'phone' => '67'
    ],
    [
        'id'    => 26,
        'email' => 'test26@test.com',
        'name'  => 'test26',
        'phone' => '26'
    ],
];

$colloborators = ['34', '17', '67', '745'];

//$result = $task->getUsers($users, $colloborators);

var_dump($result);




