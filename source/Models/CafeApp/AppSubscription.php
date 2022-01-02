<?php


namespace Source\Models\CafeApp;


use Source\Core\Model;

// Está classe será a responsável por mandar na assinatura dos usuários

class AppSubscription extends Model
{

    public function __construct()
    {
        parent::__construct("app_subscriptions", ["id"], ["user_id", "plan_id", "card_id", "status", "pay_status",
            "started", "due_day", "next_due"]);
    }

}