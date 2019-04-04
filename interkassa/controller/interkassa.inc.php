<?php

namespace Interkassa\Model\PaymentType;

use \RS\Orm\Type;
use \Shop\Model\Orm\Transaction;

/**
 * Способ оплаты - Interkassa
 */
class Interkassa extends \RS\Controller\Front
{
    function actionDoPay()
    {
        $this->wrapOutput(false);
        $order_id = $this->url->request('order_id', TYPE_STRING);

        $transactionApi = new \Shop\Model\TransactionApi();
        $transaction = $transactionApi->createTransactionFromOrder($order_id);

        if ($transaction->getPayment()->getTypeObject()->isPostQuery()) { //Если нужен пост запрос
            $url = $transaction->getPayUrl();

            $this->view->assign(array(
                'url' => $url,
                'transaction' => $transaction
            ));

            $this->wrapOutput(false);

            return $this->result->setTemplate("%shop%/onlinepay/post.tpl");
        } else {
            $this->redirect($transaction->getPayUrl());
        }
    }
}