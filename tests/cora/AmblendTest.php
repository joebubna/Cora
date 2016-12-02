<?php
namespace Tests\Cora;

class AmblendTest extends \Cora\App\TestCase
{   
    /**
     *  @test
     */
    public function canCreateSimpleModel()
    {
        //$this->app->dbBuilder->reset();
        $users = $this->app->tests->users;
        $this->assertEquals($users->count(), 0);
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);
        $this->assertEquals($users->count(), 1);
    }

    /**
     *  @test
     */
    public function canCreateInheritedSubFolderModel()
    {
        //$this->app->dbBuilder->reset();
        $userComments = $this->app->tests->userComments;
        $this->assertEquals($userComments->count(), 0);
        $comment = new \Models\Tests\Users\Comment(null, 'Test comment');
        $userComments->save($comment);
        $this->assertEquals($userComments->count(), 1);
    }

    /**
     *  @test
     */
    public function canReferenceSubFolderModelUsingVia_independent()
    {
        //$this->app->dbBuilder->reset();
        $users = $this->app->tests->users;
        $userComments = $this->app->tests->userComments;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Rather than have just ID be populated into a new object like this,
        // maybe have it use Populate so that related model references will at least
        // return an empty set.

        //$this->assertEquals($user->comments->count(), 0)
        var_dump($user);

        $comment = new \Models\Tests\Users\Comment($user->id, 'Test comment');
        $userComments->save($comment);

        $this->assertEquals($user->comments->count(), 1);
    }

}