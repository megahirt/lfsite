<?php

namespace libraries\scriptureforge\sfchecks;


use models\ProjectModel;
use models\QuestionAnswersListModel;
use models\scriptureforge\dto\UsxHelper;
use models\shared\rights\ProjectRoles;
use models\TextListModel;
use models\TextModel;
use models\UserList_ProjectModel;
use models\UserModel;

class SfchecksReports {

    public static function TopContributorsWithTextReport($projectId) {
        $project = ProjectModel::getById($projectId);
        $output = str_pad('**** Top Responders With Responses Report ****', 120, " ", STR_PAD_BOTH) . "\n";
        $output .= str_pad(date(DATE_RFC2822), 120, " ", STR_PAD_BOTH) . "\n\n";
        $data = array();
        $contributors = array();

        $listModel = new UserList_ProjectModel($projectId);
        $listModel->read();
        if ($listModel->count > 0) {
            $textListModel = new TextListModel($project);
            $textListModel->read();
            $questions = array();
            foreach($textListModel->entries as $text) {
                $questionListModel = new QuestionAnswersListModel($project, $text['id']);
                $questionListModel->read();
                $questions = array_merge($questions,
                    array_map(
                        function($q) use ($text) {
                            $q['textRef'] = $text['id'];
                            return $q;
                        },
                        $questionListModel->entries)
                );
            }

            $answerCtr = 0;
            $commentCtr = 0;

            foreach($listModel->entries as $user) {
                $userModel = new UserModel($user['id']);
                $user['isActive'] = $userModel->active;
                $user['answers'] = 0;
                $user['comments'] = 0;
                $user['questions'] = 0;
                $user['responses'] = 0;
                $user['textIds'] = array();
                if (!$user['isActive']) {
                    continue;
                }
                if ($project->users->offsetExists($user['id'])) {
                    $user['role'] = $project->users[$user['id']]->role;
                } else {
                    $user['role'] = ProjectRoles::NONE;
                }
                if ($user['role'] == ProjectRoles::MANAGER) {
                    continue;
                }
                $answerCtr = 0;
                $commentCtr = 0;
                $user['responsesList'] = array();
                foreach($questions as $question) {
                    $responses = 0;
                    foreach($question['answers'] as $answer) {
                        if (!$answer['content']) {
                            continue;
                        }
                        $answerCtr++;
                        foreach($answer['comments'] as $comment) {
                            if (!$comment['content']) {
                                continue;
                            }
                            $commentCtr++;
                            if ($comment['userRef'] && $comment['userRef']->{'$id'} == $user['id']) {
                                $user['comments']++;
                                $user['responses']++;
                                array_push($user['textIds'], $question['textRef']);
                                $responses++;
                                self::_addResponseToList($user['responsesList'], array(
                                    'timestamp' => $comment['dateEdited']->sec,
                                    'comment' => $comment['content'],
                                    'answer' => strip_tags($answer['content']),
                                    'answerSelectedText' => strip_tags($answer['textHighlight']),
                                    'question' => $question['title'] . ' / ' . $question['description'],
                                    'textId' => $question['textRef'],
                                    'questionId' => $question['id'],
                                    'text' => self::_getTextContent($project, $question['textRef'])
                                ));
                            }
                        }
                        if ($answer['userRef'] && $answer['userRef']->{'$id'} == $user['id']) {
                            $user['answers']++;
                            $user['responses']++;
                            array_push($user['textIds'], $question['textRef']);
                            $responses++;
                            self::_addResponseToList($user['responsesList'], array(
                                'timestamp' => $comment['dateEdited']->sec,
                                'answer' => strip_tags($answer['content']),
                                'answerSelectedText' => strip_tags($answer['textHighlight']),
                                'comment' => '',
                                'question' => $question['title'] . ' / ' . $question['description'],
                                'textId' => $question['textRef'],
                                'questionId' => $question['id'],
                                'text' => self::_getTextContent($project, $question['textRef'])
                            ));
                        }
                    }
                    if ($responses > 0) {
                        $user['questions']++;
                    }
                }
                if ($user['responses'] > 0) {
                    $user['texts'] = count(array_unique($user['textIds']));
                    array_push($contributors, $user);
                }
            }

            $output .= $project->projectName . " Project\n";
            $output .= "Texts (T's) in Project: " . $textListModel->count . "\n";
            $output .= "Questions (Q's) in Project: " . count($questions) . "\n";
            $output .= "Responses (R's) in Project (Answers + Comments): " . ($answerCtr + $commentCtr) . "\n";
            $output .= "Answers (A's) in Project: " . $answerCtr . "\n";
            $output .= "Comments (C's) in Project: " . $commentCtr . "\n";
        } else {
            $output .= "This project has no users\n\n";
        }

        $sortByResponseCount = function($a, $b) {
            if ($a['responses'] > $b['responses']) {
                return -1;
            } else {
                return 1;
            }
        };

        usort($contributors, $sortByResponseCount);


        $topContributors = array_slice($contributors, 0, 10);

        $output .= "\nTop " . count($topContributors) . " Contributors";
        foreach($topContributors as $user) {
            $output .= "\n\n" . str_pad("Name", 30) . str_pad("Email", 35) . str_pad("Username", 25) .
                str_pad("R's", 5) . str_pad("A's", 5) . str_pad("C's", 5) . str_pad("Q's", 5) . str_pad("T's", 5) . "\n\n";
            $output .= str_pad($user['name'], 30) . str_pad($user['email'], 35) . str_pad($user['username'], 25) .
                str_pad($user['responses'], 5) . str_pad($user['answers'], 5) . str_pad($user['comments'], 5) . str_pad($user['questions'], 5) . str_pad($user['texts'], 5) . "\n\n";

            foreach($user['responsesList']['texts'] as $text) {
                $output .= "\nIn reference to the text " . $text['title'] . "\n\n" . $text['content'] . "\n";
                foreach ($text['questions'] as $question) {
                    $output .= "\n\tIn reference to the question '" . $question['content'] . "'\n";
                    foreach ($question['answers'] as $answer) {
                        $date = new \DateTime('@' . $answer['timestamp']);

                        if ($answer['selectedText']) {
                            $answer['answer'] = "(reference: '" . $answer['selectedText'] . "') " . $answer['answer'];
                        }

                        if (!$answer['comment']) {
                            $output .= "\n\t\t" . str_pad($date->format('M d, Y'), 15) . $answer['answer'] . "\n";
                        } else {
                            $output .= "\n\t\t" . "Commented on the answer '" . $answer['answer'] . "'\n";
                            $output .= "\t\t\t" . str_pad($date->format('M d, Y'), 15) . $answer['comment'] . "\n";
                        }
                    }
                }
            }
        }


        $data['output'] = $output;
        $data['result'] = array('topContributors' => $topContributors);
        return $data;

    }

    private static function _getTextContent($projectModel, $textId) {
        if ($textId) {
            $text = new TextModel($projectModel, $textId);
            $usxHelper = new UsxHelper($text->content);
            $html = preg_replace('/<p[^>]*>/', ' ', $usxHelper->toHtml());
            $html = preg_replace('/<div[^>]*>/', ' ', $html);
            $html = preg_replace('/<\/sup[^>]*>/', ' ', $html);
            $textContent = strip_tags($html);
            return array('title' => $text->title, 'content' => $textContent);
        }
    }

    private static function _addResponseToList(&$list, $data) {
        $textId = $data['textId'];
        $questionId = $data['questionId'];
        if (!array_key_exists('texts', $list)) {
            $list['texts'] = array();
        }
        if (!array_key_exists($textId, $list['texts'])) {
            $list['texts'][$textId] = array();
        }
        if (!array_key_exists('questions', $list['texts'][$textId])) {
            $list['texts'][$textId]['questions'] = array();
        }
        if (!array_key_exists($questionId, $list['texts'][$textId]['questions'])) {
            $list['texts'][$textId]['questions'][$questionId] = array();
        }
        if (!array_key_exists('answers', $list['texts'][$textId]['questions'][$questionId])) {
            $list['texts'][$textId]['questions'][$questionId]['answers'] = array();
        }

        $list['texts'][$textId]['title'] = $data['text']['title'];
        $list['texts'][$textId]['content'] = $data['text']['content'];
        $list['texts'][$textId]['questions'][$questionId]['content'] = $data['question'];
        $list['texts'][$textId]['questions'][$questionId]['answers'][] = array(
            'answer' => $data['answer'],
            'selectedText' => $data['answerSelectedText'],
            'comment' => $data['comment'],
            'timestamp' => $data['timestamp']
        );
    }






    public static function UserEngagementReport($projectId) {
        $project = ProjectModel::getById($projectId);
        $output = str_pad('**** User Engagement Report ****', 120, " ", STR_PAD_BOTH) . "\n";
        $output .= str_pad(date(DATE_RFC2822), 120, " ", STR_PAD_BOTH) . "\n\n";
        $data = array();
        $activeUsers = array();
        $managerUsers = array();
        $inactiveUsers = array();
        $invalidUsers = array();


        $listModel = new UserList_ProjectModel($projectId);
        $listModel->read();
        if ($listModel->count > 0) {
            $textListModel = new TextListModel($project);
            $textListModel->read();
            $questions = array();
            foreach($textListModel->entries as $text) {
                $questionListModel = new QuestionAnswersListModel($project, $text['id']);
                $questionListModel->read();
                $questions = array_merge($questions,
                    array_map(
                        function($q) use ($text) {
                            $q['textRef'] = $text['id'];
                            return $q;
                        },
                        $questionListModel->entries)
                );
            }

            $answerCtr = 0;
            $commentCtr = 0;

            foreach($listModel->entries as $user) {
                $userModel = new UserModel($user['id']);
                $user['isActive'] = $userModel->active;
                $user['questions'] = 0;
                $user['texts'] = 0;
                $user['answers'] = 0;
                $user['comments'] = 0;
                $user['responses'] = 0;
                $user['textIds'] = array();
                if (!$user['isActive']) {
                    if (!$user['email']) {
                        $user['email'] = $userModel->emailPending;
                    }
                    array_push($invalidUsers, $user);
                    continue;
                }
                if ($project->users->offsetExists($user['id'])) {
                    $user['role'] = $project->users[$user['id']]->role;
                } else {
                    $user['role'] = ProjectRoles::NONE;
                }
                $answerCtr = 0;
                $commentCtr = 0;
                foreach($questions as $question) {
                    $responses = 0;
                    foreach($question['answers'] as $answer) {
                        if (!$answer['content']) {
                            continue;
                        }
                        $answerCtr++;
                        foreach($answer['comments'] as $comment) {
                            if (!$comment['content']) {
                                continue;
                            }
                            $commentCtr++;
                            if ($comment['userRef'] && $comment['userRef']->{'$id'} == $user['id']) {
                                $user['comments']++;
                                $user['responses']++;
                                array_push($user['textIds'], $question['textRef']);
                                $responses++;
                            }
                        }
                        if ($answer['userRef'] && $answer['userRef']->{'$id'} == $user['id']) {
                            $user['answers']++;
                            $user['responses']++;
                            array_push($user['textIds'], $question['textRef']);
                            $responses++;
                        }
                    }
                    if ($responses > 0) {
                        $user['questions']++;
                    }
                }
                $user['texts'] = count(array_unique($user['textIds']));
                if ($user['role'] == ProjectRoles::MANAGER) {
                    array_push($managerUsers, $user);
                }
                elseif ($user['responses'] > 0) {
                    array_push($activeUsers, $user);
                } else {
                    array_push($inactiveUsers, $user);
                }
            }

            $output .= $project->projectName . " Project\n";
            $output .= "Texts (T's) in Project: " . $textListModel->count . "\n";
            $output .= "Questions (Q's) in Project: " . count($questions) . "\n";
            $output .= "Responses (R's) in Project (Answers + Comments): " . ($answerCtr + $commentCtr) . "\n";
            $output .= "Answers (A's) in Project: " . $answerCtr . "\n";
            $output .= "Comments (C's) in Project: " . $commentCtr . "\n";
        } else {
            $output .= "This project has no users\n\n";
        }

        $sortByResponses = function($a, $b) {
            if ($a['responses'] > $b['responses']) {
                return -1;
            } elseif ($a['responses'] < $b['responses']) {
                return 1;
            } else {
                if ($a['answers'] > $b['answers']) {
                    return -1;
                } elseif ($a['answers'] < $b['answers']) {
                    return 1;
                } else {
                    if ($a['comments'] > $b['comments']) {
                        return -1;
                    } elseif ($a['comments'] < $b['comments']) {
                        return 1;
                    } else {
                        return strcmp($a['username'], $b['username']);
                    }
                }
            }
        };

        $sortByName = function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        };

        usort($activeUsers, $sortByResponses);
        usort($managerUsers, $sortByResponses);
        usort($inactiveUsers, $sortByName);
        usort($invalidUsers, $sortByName);

        $output .= "\n\nManagers: " . count($managerUsers) . "\n" . str_pad("Name", 30) . str_pad("Email", 35) . str_pad("Username", 25) .
            str_pad("R's", 5) . str_pad("A's", 5) . str_pad("C's", 5) . str_pad("Q's", 5) . str_pad("T's", 5) . "\n\n";
        foreach($managerUsers as $user) {
            $output .= str_pad($user['name'], 30) . str_pad($user['email'], 35) . str_pad($user['username'], 25) .
                str_pad($user['responses'], 5) . str_pad($user['answers'], 5) . str_pad($user['comments'], 5) . str_pad($user['questions'], 5) . str_pad($user['texts'], 5) . "\n";
        }

        $output .= "\n\nActive Users: " . count($activeUsers) . "\n" . str_pad("Name", 30) . str_pad("Email", 35) . str_pad("Username", 25) .
            str_pad("R's", 5) . str_pad("A's", 5) . str_pad("C's", 5) . str_pad("Q's", 5) . str_pad("T's", 5) . "\n\n";
        foreach($activeUsers as $user) {
            $output .= str_pad($user['name'], 30) . str_pad($user['email'], 35) . str_pad($user['username'], 25) .
                str_pad($user['responses'], 5) . str_pad($user['answers'], 5) . str_pad($user['comments'], 5) . str_pad($user['questions'], 5) . str_pad($user['texts'], 5) . "\n";
        }

        $output .= "\n\nInactive Users (never engaged): " . count($inactiveUsers) . "\n" . str_pad("Name", 30) . str_pad("Email", 35) . str_pad("Username", 25) . "\n\n";
        foreach($inactiveUsers as $user) {
            $output .= str_pad($user['name'], 30) . str_pad($user['email'], 35) . str_pad($user['username'], 25) . "\n";
        }

        $output .= "\n\nInvited Users (but never validated or logged in): " . count($invalidUsers) . "\n" . str_pad("Name", 30) . str_pad("Email", 35) . str_pad("Username", 25) . "\n\n";
        foreach($invalidUsers as $user) {
            $output .= str_pad($user['name'], 30) . str_pad($user['email'], 35) . str_pad($user['username'], 25) . "\n";
        }

        $data['output'] = $output;
        $data['result'] = array('managerUsers' => $managerUsers, 'activeUsers' => $activeUsers, 'inactiveUsers' => $inactiveUsers, 'invitedUsers' => $invalidUsers);
        return $data;
    }



}