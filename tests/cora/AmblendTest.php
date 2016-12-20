<?php
namespace Tests\Cora;

class AmblendTest extends \Cora\App\TestCase
{   
    /**
     *  Check that it's possible to create a simple model located that doesn't inherit from another model.
     *
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
     *  Check that it's possible to create a model that inherits from another model.
     *
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
     *  Adds a new related object to a User without adding it directly to the User object.
     *  Check that it's possible to use a Comment repository to add a comment to a User without
     *  directly adding it through the User model.
     *
     *  Also checks that:
     *  - References using "Via" keyword work. 
     *  - References to models in subfolders works.
     *
     *  @test
     */
    public function canReferenceSubFolderModelUsingVia_independent()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;
        $userComments = $this->app->tests->userComments;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no comments.
        $this->assertEquals($user->comments->count(), 0);

        // Create comment using userComments repo.
        $comment = new \Models\Tests\Users\Comment($user->id, 'Test comment');
        $userComments->save($comment);

        // Check that user now has comment. Need to fetch user fresh from DB to ensure new comment is fetched and 
        // cached empty set isn't used instead.
        $this->assertEquals($users->find($user->id)->comments->count(), 1);
    }


    /**
     *  Adds related objects directly to a User, then saves via two different methods.
     *  Checks that it's possible to use the add() method to add new related models to another model. 
     *
     *  Also checks that:
     *  - References using "Via" keyword work. 
     *  - References to models in subfolders works.
     *
     *  @test
     */
    public function canReferenceSubFolderModelUsingVia_connected()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;
        $userComments = $this->app->tests->userComments;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no comments.
        $this->assertEquals($user->comments->count(), 0);

        // Create new comment and add to User via normal repo call.
        $user->comments->add(new \Models\Tests\Users\Comment($user->id, 'Test comment 1'));
        $this->app->tests->users->save($user);

        // Check that user has now has 1 comment
        $this->assertEquals($user->comments->count(), 1);

        // Create new comment and add to User via active record type call.
        $user->comments->add(new \Models\Tests\Users\Comment($user->id, 'Test comment 2'));
        $user->save();

        // Check that user has now has 2 comments
        $this->assertEquals($user->comments->count(), 2);

        // Pull user fresh from DB just to make sure comments were saved on server.
        $this->assertEquals($users->find($user->id)->comments->count(), 2);
    }

}