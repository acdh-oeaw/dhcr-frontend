<?php

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Mailer\Mailer;
use Exception;

class ReviewRemindersCommand extends Command
{
    private function getUseradmins()
    {
        $useradmins = $this->Users->find()->where([
            'user_admin' => 1,
            'active' => 1,
        ]);
        return $useradmins;
    }

    private function generateOneliner() {
        $oneliners = [
            "Reviewing GitHub issues is like being the hero in a digital adventure – no cape required, just a willingness to make the code world a better place!",
            "Think of GitHub issues as your code's post-it notes – reviewing them is like giving your project a daily dose of sticky motivation!",
            "You don't need to speak binary to be a GitHub superhero – reviewing issues is the non-technical way to save the day for your favorite project!",
            "GitHub issues are like the breadcrumbs in the coding forest – follow them, and you'll end up making the codebase a magical place for everyone!",
            "Reviewing GitHub issues is like being the detective in a code mystery novel – no Sherlock hat needed, just a curious mind and a cup of coffee!",
            "You don't have to be a coding wizard to review GitHub issues – just think of it as giving your project a digital high-five for a job well done!",
            "GitHub issues are the jigsaw puzzles of the coding world – no need for a computer science degree, just a knack for putting pieces together!",
            "Reviewing GitHub issues is the non-technical equivalent of decluttering your code's closet – because even projects need a Marie Kondo moment!",
            "You don't need a PhD in programming to be a GitHub guru – reviewing issues is the passport to the exciting world of code improvement!",
            "GitHub issues are like the IKEA instructions of coding – even non-techies can help assemble a better project with just a bit of review magic!",
            "Reviewing GitHub issues is the code version of being a friendly neighbor – you don't need to understand the language, just be willing to lend a helping hand!",
            "You don't have to speak in code to contribute – reviewing GitHub issues is like leaving positive comments on your project's digital fridge!",
            "GitHub issues are the paint-by-numbers of the coding canvas – even if you can't draw a straight line of code, you can help add some color to the project!",
            "Reviewing GitHub issues is like being the project's motivational speaker – no technical jargon needed, just a few words of encouragement for your code comrades!",
            "You don't need a PhD in algorithms to make a difference – reviewing GitHub issues is the non-geeky way to sprinkle some joy into your project's code!"
        ];
        $randomIndex = array_rand($oneliners);
        return $oneliners[$randomIndex];
    }

    private function sendReviewReminders($issuesAmount, $issuesUrl)
    {
        $useradmins = $this->getUseradmins();
        $subject = "[DH Course Registry] Your Attention Needed on $issuesAmount GitHub Issues";
        $oneliner = $this->generateOneliner();
        $totalMails = 0;
        foreach ($useradmins as $useradmin) {
            try {
                $mailer = new Mailer();
                $mailer->setFrom(env('APP_MAIL_DEFAULT_FROM'))
                    ->setSender(env('APP_MAIL_DEFAULT_FROM'))
                    ->setReturnPath(env('APP_MAIL_DEFAULT_FROM'))
                    ->setTo($useradmin->email)
                    ->setSubject($subject)
                    ->setViewVars(['firstName' => $useradmin->first_name,
                                    'issuesAmount' => $issuesAmount,
                                    'issuesUrl' => $issuesUrl,
                                    'oneliner' => $oneliner])
                    ->viewBuilder()->setTemplate('review_reminders/review_reminder');
                $mailer->deliver();
                $totalMails++;
            } catch (Exception $ex) {
                $action = 'Error sending mail';
                $details = 'Useradmin: ' .$useradmin->email;
                echo "$action $details \n";
                $scriptName = basename(__FILE__, '.php');
                $scriptName = str_replace('Command', '', $scriptName);
                $this->Logentries->createLogEntry(
                    '50',
                    '586',
                    $scriptName,
                    $action,
                    $details
                );
            }
            echo ".";   // progress indicator
        }
        echo "\n";      // close progress indicator        
        $action = 'Result:';
        $details = $totalMails . ' mails sent.';
        $scriptName = basename(__FILE__, '.php');
        $scriptName = str_replace('Command', '', $scriptName);
        $this->Logentries->createLogEntry(
            '30',
            '586',
            $scriptName,
            $action,
            $details
        );
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->loadModel('Users');
        $this->loadModel('Logentries');
        $io->out('~~~ Started Review Reminders ~~~');
        $issuesUrl = 'https://github.com/acdh-oeaw/dhcr-main/labels/inreview';
        $content = file_get_contents($issuesUrl);
        $pattern = '/\b\d+\sOpen\b/i';
        if (preg_match($pattern, $content, $lines)) {
            preg_match('/\d+/', $lines[0], $result);
            $issuesAmount = $result[0];
            $io->out('Amount of open issues: ' . $issuesAmount);
            if ($issuesAmount > 0) {
                $this->sendReviewReminders($issuesAmount, $issuesUrl);
            } else {
                $action = 'Result:';
                $details = 'No issues open';
                $io->out($details);

                $scriptName = basename(__FILE__, '.php');
                $scriptName = str_replace('Command', '', $scriptName);
                $this->Logentries->createLogEntry(
                    '30',
                    '586',
                    $scriptName,
                    $action,
                    $details
                );
            }
        } else {
            $action = 'Error:';
            $details = 'Error finding amount of open issues';
            $io->out($details);

            $scriptName = basename(__FILE__, '.php');
            $scriptName = str_replace('Command', '', $scriptName);
            $this->Logentries->createLogEntry(
                '50',
                '586',
                $scriptName,
                $action,
                $details
            );
        }
        $io->out('~~~ Finished Review Reminders ~~~');
    }
}
