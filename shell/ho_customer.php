<?php

require_once 'abstract.php';

class Ho_Customer_Shell extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     * @return void
     */
    public function run() {
        $action = $this->getArg('action');
        if (empty($action)) {
            echo $this->usageHelp();
        } else {
            $actionMethodName = $action.'Action';
            if (method_exists($this, $actionMethodName)) {
                $this->$actionMethodName();
            } else {
                echo "Action $action not found!\n";
                echo $this->usageHelp();
                exit(1);
            }
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     * @return string
     */
    public function usageHelp() {
        $help = 'Available actions: ' . "\n";
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (substr($method, -6) == 'Action') {
                $help .= '    -action ' . substr($method, 0, -6);
                $helpMethod = $method.'Help';
                if (method_exists($this, $helpMethod)) {
                    $help .= '    ' . $this->$helpMethod();
                }
                $help .= "\n";
            }
        }
        return $help;
    }

    public function convertGuestOrdersAction()
    {
        $observer = Mage::getModel('ho_customer/guestOrders');

        $start = $this->getArg('start');

        if (is_numeric($start)) {
            echo "Converting guest orders starting from entity ID $start...\n";
            $result = $observer->registerCustomers($start);
        } else {
            echo "Converting guest orders...\n";
            $result = $observer->registerCustomers();
        }

        echo "$result\n";
    }
}

$shell = new Ho_Customer_Shell();
$shell->run();
