<?php

class TestCase extends Laravel\Lumen\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/app.php';
    }
    
    /**
     * 封装artisan命令以方便使用phpunit做单元测试
     * PS: commonds输出必须为标准输出
     * e.g. $ret = $this->artisan('job_xxxx');
     *      $ret->see('succ');
     *      $ret->dontSee('error');
     * @return TestCase
     */
    public function artisan($command, $parameters = [])
    {
        ob_start();
        parent::artisan($command, $parameters);
        $this->visit('/')->response->setContent(ob_get_contents());
        ob_end_clean();
        return $this;
    }
}
