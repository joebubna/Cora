<?php

class ProductsTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canViewListOfProducts()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Products($this->app);
        $controller->index();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewProductPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Products($this->app);
        $controller->view(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewEditProductPage()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Products($this->app);
        $controller->edit(1);
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canViewCreateProductForm()
    {
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Products($this->app);
        $controller->create();
        $this->expectOutputString('');
    }
    
    
    /**
     *  @test
     */
    public function canCreateProduct()
    {
        $_POST['mfg']       = 'Phonak';
        $_POST['name']      = 'TestNameProduct';
        $_POST['tier']      = 1;
        $_POST['price']     = 2001;
        $_POST['type']      = 'Hearing Aid';
        $_POST['status']    = 'Active';
        
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        
        // Pre
        $product = $this->app->products->findOneBy('name', $_POST['name']);
        $this->assertEmpty($product);
        
        // Code
        $controller = new \Controllers\Admin\Products($this->app);
        $controller->createPOST();
        
        // Post
        $product = $this->app->products->findOneBy('name', $_POST['name']);
        $this->assertNotEmpty($product);
    }
    
    
    /**
     *  @test
     */
    public function canEditPatients()
    {
        // Set inputs
        $id = 1;
        $_POST['mfg']       = 'Oticon';
        $_POST['name']      = 'EditTestNameProduct';
        $_POST['tier']      = 2;
        $_POST['price']     = 2003;
        $_POST['type']      = 'Accessory';
        $_POST['status']    = 'Deactivated';
        
        // Check pre-result
        $product = $this->app->products->find($id);
        $this->assertNotEquals($_POST['name'], $product->name);
        $this->assertNotEquals($_POST['price'], $product->price);
        
        // Execute code
        $this->app->auth->login('admin@fuelmedical.com', 'test');
        $controller = new \Controllers\Admin\Products($this->app);
        $controller->editPOST($id);
        
        // Check result is as expected
        $product = $this->app->products->find($id);
        $this->assertEquals($_POST['name'], $product->name);
        $this->assertEquals($_POST['price'], $product->price);
    }
}