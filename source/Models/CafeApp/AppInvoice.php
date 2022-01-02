<?php

namespace Source\Models\CafeApp;

use Source\Core\Model;
use Source\Core\Session;
use Source\Models\User;

/**
 * Class AppInvoice
 * @package Source\Models\CafeApp
 */
class AppInvoice extends Model
{
    /** @var null|int  */
    public $wallet;

    /**
     * Este PHP Class será a minha fatura no sistema
     */

    public function __construct()
    {
        parent::__construct("app_invoices", ["id"],
            ["user_id", "wallet_id", "category_id", "description", "type", "value", "due_at", "repeat_when"]);

        if ((new Session())->has("walletfilter")) {
            $this->wallet = "AND wallet_id = " . (new Session())->walletfilter;
        }
    }


    /**
     * @param User $user
     * @param int $afterMonths
     * @throws \Exception
     */
    public function fixed (User $user, int $afterMonths = 1): void
    {
        $fixed = $this->find("user_id = :user AND status = 'paid' AND type IN('fixed_income', 'fixed_expense') {$this->wallet}",
        "user={$user->id}")->fetch(true);

        // quer dizer que não tenho conta fixa lançada
        if (!$fixed) {
            return;
        }

        foreach ($fixed as $fixedItem) {
            $invoice = $fixedItem->id;
            $start = new \DateTime($fixedItem->due_at);
            $end = new \DateTime("+{$afterMonths}month");


            // Verificando o periodo

            if ($fixedItem->period == "month") {
                $interval = new \DateInterval("P1M");
            }

            if ($fixedItem->period == "treemonth") {
                $interval = new \DateInterval("P3M");
            }

            if ($fixedItem->period == "sixmonth") {
                $interval = new \DateInterval("P6M");
            }

            if ($fixedItem->period == "year") {
                $interval = new \DateInterval("P1Y");
            }

            $period = new \DatePeriod($start, $interval, $end);
            foreach ($period as $item) {
                // $getFixed verificando se já existe a fatura no mês e no ano atual ligada a invoice_of que é a recorrencia, se não tiver irei fazer a criação

            }
                $getFixed = $this->find("user_id = :user AND invoice_of = :of AND year(due_at) = :y AND month(due_at) = :m",
                    "user={$user->id}&of={$fixedItem->id}&y={$item->format("Y")}&m={$item->format("m")}",
                    "id")->fetch();

            if (!$getFixed) {
                $newItem = $fixedItem;
                $newItem->id = null;
                $newItem->invoice_of = $invoice;
                $newItem->type = str_replace("fixed_", "", $newItem->type);
                $newItem->due_at = $item->format("Y-m-d");
                $newItem->status = ($item->format("Y-m-d") <= date("Y-m-d") ? "paid" : "unpaid");
                $newItem->save();
            }
        }
    }

    /**
     * @param User $user
     * @param string $type
     * @param array|null $filter
     * @param int|null $limit
     * @return array|null
     */
    public function filter (User $user, string $type, ?array $filter, ?int $limit = null): ?array
    {
        $status = (!empty($filter["status"]) && $filter["status"] == "paid" ? "AND status = 'paid'" : (!empty($filter["status"]) && $filter["status"] == "unpaid" ? "AND status = 'unpaid'" : null));
        $category = (!empty($filter["category"]) && $filter["category"] != "all" ? "AND category_id = '{$filter["category"]}'" : null);

        $due_year = (!empty($filter["date"]) ? explode("-", $filter["date"])[1] : date("Y"));
        $due_month = (!empty($filter["date"]) ? explode("-", $filter["date"])[0] : date("m"));
        $due_at = "AND (year(due_at) = '{$due_year}' AND month(due_at) = '{$due_month}')";

        $due = $this->find(
            "user_id = :user AND type = :type {$status} {$category} {$due_at} {$this->wallet}",
            "user={$user->id}&type={$type}"
        )->order("day(due_at) ASC");

        if ($limit) {
            $due->limit($limit);
        }

        return $due->fetch(true);

    }

    /**
     * @return AppCategory
     */
    public function category (): AppCategory
    {
        return (new AppCategory())->findById($this->category_id);
    }

    /**
     * @param User $user
     * @return object
     */
    public function balance (User $user): object
    {
        $balance = new \stdClass();
        $balance->income = 0;
        $balance->expense = 0;
        $balance->wallet = 0;
        $balance->balance = "positive";

        $find = $this->find("user_id = :user AND status = :status",
            "user={$user->id}&status=paid",
            "
                (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income' {$this->wallet}) AS income,
                (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense' {$this->wallet}) AS expense
            ")->fetch();

        if ($find) {
            $balance->income = abs($find->income);
            $balance->expanse = abs($find->expense);
            $balance->wallet = $balance->income - $balance->expanse;
            $balance->balance = ($balance->wallet >= 1 ? "positive" : "negative");
        }

        return $balance;
    }

    public function balanceWallet (AppWallet $wallet): object
    {
        $balance = new \stdClass();
        $balance->income = 0;
        $balance->expense = 0;
        $balance->wallet = 0;
        $balance->balance = "positive";

        $find = $this->find("user_id = :user AND status = :status",
            "user={$wallet->user_id}&status=paid",
            "(SELECT SUM (value) FROM app_invoices WHERE user_id = :user AND wallet_id = {$wallet->id} AND status = :status AND type = 'income') AS income, 
            (SELECT SUM (value) FROM app_invoices WHERE user_id = :user AND wallet_id = {$wallet->id} AND status = :status AND type = 'income') AS expense,")
        ->fetch();

        // o if diz que tive o resultado da carteira

        if ($find) {
            $balance->income = abs($find->income);
            $balance->expense = abs($find->expense);
            $balance->wallet = $balance->income - $balance->expense;
            $balance->balance = ($balance->wallet >= 1 ? "positive" : "negative");
        }

        return $balance;

    }

    /**
     * @param User $user
     * @param int $year
     * @param int $month
     * @param string $type
     * @return object|null
     */
    public function balanceMonth (User $user, int $year, int $month, string $type): ?object
    {
        $onpaid = $this->find(
            "user_id = :user",
            "user={$user->id}&type={$type}&year={$year}&month={$month}",
            "
                (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND type = :type AND year(due_at) = :year AND month(due_at) = :month AND status = 'paid' {$this->wallet}) AS paid,
                (SELECT SUM(value) FROM app_invoices WHERE user_id = :user AND type = :type AND year(due_at) = :year AND month(due_at) = :month AND status = 'unpaid' {$this->wallet}) AS unpaid
            "
        )->fetch();

        if (!$onpaid) {
            return null;
        }

        return (object)[
            "paid" => str_price(($onpaid->paid ?? 0)),
            "unpaid" => str_price(($onpaid->unpaid ?? 0))
        ];
    }

    /**
     * @param User $user
     * @return object
     */
    public function chartData(User $user): object
    {
        // CHART - Inicio do gráfico

        $dateChart = [];
        // Criando um looping para puxar os dados de 4 meses atrás
        for ($month = -4; $month <=0; $month++) {
            $dateChart[] = date("m/Y", strtotime("{$month}month"));
        }

        $chartData = new \stdClass();
        // utilizo o implode para unificar as datas
        $chartData->categories = "'" . implode("','", $dateChart) . "'";
        $chartData->expense = "0,0,0,0,0";
        $chartData->income = "0,0,0,0,0";

        $chart = (new AppInvoice())->find("user_id = :user AND status = :status AND due_at >= DATE(now() - INTERVAL 4 MONTH) GROUP BY year(due_at) ASC, month(due_at) ASC",
            "user={$user->id}&status=paid",
            "year(due_at) AS due_year,
                month(due_at) AS due_month,
                DATE_FORMAT(due_at, '%m/%y') AS due_date,
                (SELECT SUM(VALUE) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'income' AND year(due_at) = due_year AND month(due_at) = due_month {$this->wallet}) AS income,
                (SELECT SUM(VALUE) FROM app_invoices WHERE user_id = :user AND status = :status AND type = 'expense' AND year(due_at) = due_year AND month(due_at) = due_month {$this->wallet}) AS expense
                ")
            ->limit(5)->fetch(true);

        if ($chart) {
            $chartCategories = [];
            $chartExpense = [];
            $chartIncome = [];

            foreach ($chart as $chartItem) {
                $chartCategories [] = $chartItem->due_date;
                $chartExpense [] = $chartItem->expense;
                $chartIncome [] = $chartItem->income;
            }

            $chartData->categories = "'" . implode("','", $chartCategories) . "'";
            // array map transforma os numeros positivos em inteiros
            $chartData->expense = implode(",", array_map("abs", $chartExpense));
            $chartData->income = implode(",", array_map("abs", $chartIncome));
        }

        return $chartData;
    }

    // END CHART - Fim do gráfico

}