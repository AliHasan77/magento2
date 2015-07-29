<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Sales\Model\Order\Payment\Transaction;

use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

class Manager implements ManagerInterface
{
    /**
     * @var \Magento\Sales\Api\TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @param \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository
     */
    public function __construct(\Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationTransaction($parentTransactionId, $paymentId, $orderId)
    {
        $transaction = false;
        if ($parentTransactionId) {
            $transaction = $this->transactionRepository->getByTxnId(
                $parentTransactionId,
                $paymentId,
                $orderId
            );
        }

        return $transaction ?: $this->transactionRepository->getByTxnType(
            Transaction::TYPE_AUTH,
            $paymentId,
            $orderId
        );
    }

    /**
     * Checks if transaction exists by txt id
     *
     * @param string $transactionId
     * @return bool
     */
    public function isTransactionExists($transactionId, $paymentId, $orderId)
    {
        return $transactionId && $this->transactionRepository->getByTxnId($transactionId, $paymentId, $orderId);
    }

    /**
     * Update transaction ids for further processing
     * If no transactions were set before invoking, may generate an "offline" transaction id
     *
     * @param OrderPaymentInterface $payment
     * @param string $type
     * @param bool|Transaction $transactionBasedOn
     * @return string|null
     */
    public function generateTransactionId(OrderPaymentInterface $payment, $type, $transactionBasedOn = false)
    {
        if (!$payment->getParentTransactionId() && !$payment->getTransactionId() && $transactionBasedOn) {
            $payment->setParentTransactionId($transactionBasedOn->getTxnId());
        }
        // generate transaction id for an offline action or payment method that didn't set it
        if (($parentTxnId = $payment->getParentTransactionId()) && !$payment->getTransactionId()) {
            return "{$parentTxnId}-{$type}";
        }
        return $payment->getTransactionId();
    }
}