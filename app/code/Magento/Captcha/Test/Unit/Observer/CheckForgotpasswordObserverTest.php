<?php declare(strict_types=1);
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Captcha\Test\Unit\Observer;

use Magento\Captcha\Helper\Data;
use Magento\Captcha\Model\DefaultModel;
use Magento\Captcha\Observer\CaptchaStringResolver;
use Magento\Captcha\Observer\CheckForgotpasswordObserver;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CheckForgotpasswordObserverTest extends TestCase
{
    /**
     * @var CheckForgotpasswordObserver
     */
    protected $checkForgotpasswordObserver;

    /**
     * @var MockObject
     */
    protected $_helper;

    /**
     * @var MockObject
     */
    protected $_actionFlag;

    /**
     * @var MockObject
     */
    protected $_messageManager;

    /**
     * @var MockObject
     */
    protected $redirect;

    /**
     * @var ObjectManager
     */
    protected $_objectManager;

    /**
     * @var CaptchaStringResolver|MockObject
     */
    protected $captchaStringResolver;

    /**
     * @var MockObject
     */
    protected $_captcha;

    protected function setUp(): void
    {
        $this->_objectManager = new ObjectManager($this);
        $this->_helper = $this->createMock(Data::class);
        $this->_actionFlag = $this->createMock(ActionFlag::class);
        $this->_messageManager = $this->createMock(ManagerInterface::class);
        $this->redirect = $this->createMock(RedirectInterface::class);
        $this->captchaStringResolver = $this->createMock(CaptchaStringResolver::class);
        $this->checkForgotpasswordObserver = $this->_objectManager->getObject(
            CheckForgotpasswordObserver::class,
            [
                'helper' => $this->_helper,
                'actionFlag' => $this->_actionFlag,
                'messageManager' => $this->_messageManager,
                'redirect' => $this->redirect,
                'captchaStringResolver' => $this->captchaStringResolver
            ]
        );
        $this->_captcha = $this->createMock(DefaultModel::class);
    }

    public function testCheckForgotpasswordRedirects()
    {
        $formId = 'user_forgotpassword';
        $captchaValue = 'some-value';
        $warningMessage = 'Incorrect CAPTCHA';
        $redirectRoutePath = '*/*/forgotpassword';
        $redirectUrl = 'http://magento.com/customer/account/forgotpassword/';

        $request = $this->createMock(Http::class);
        $response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $request->expects(
            $this->any()
        )->method(
            'getPost'
        )->with(
            Data::INPUT_NAME_FIELD_VALUE,
            null
        )->will(
            $this->returnValue([$formId => $captchaValue])
        );

        $this->redirect->expects(
            $this->once()
        )->method(
            'redirect'
        )->with(
            $response,
            $redirectRoutePath,
            []
        )->will(
            $this->returnValue($redirectUrl)
        );

        $controller = $this->createMock(Action::class);
        $controller->expects($this->any())->method('getRequest')->will($this->returnValue($request));
        $controller->expects($this->any())->method('getResponse')->will($this->returnValue($response));
        $this->_captcha->expects($this->any())->method('isRequired')->will($this->returnValue(true));
        $this->_captcha->expects(
            $this->once()
        )->method(
            'isCorrect'
        )->with(
            $captchaValue
        )->will(
            $this->returnValue(false)
        );

        $this->captchaStringResolver->expects(
            $this->once()
        )->method(
            'resolve'
        )->with(
            $request,
            $formId
        )->will(
            $this->returnValue($captchaValue)
        );

        $this->_helper->expects(
            $this->any()
        )->method(
            'getCaptcha'
        )->with(
            $formId
        )->will(
            $this->returnValue($this->_captcha)
        );
        $this->_messageManager->expects($this->once())->method('addErrorMessage')->with($warningMessage);
        $this->_actionFlag->expects(
            $this->once()
        )->method(
            'set'
        )->with(
            '',
            Action::FLAG_NO_DISPATCH,
            true
        );

        $this->checkForgotpasswordObserver->execute(
            new Observer(['controller_action' => $controller])
        );
    }
}
