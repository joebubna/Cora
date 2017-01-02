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
        $this->assertEquals(0, $users->count());
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);
        $this->assertEquals(1, $users->count());
    }

    /**
     *  Check that it's possible to create a model that inherits from another model.
     *
     *  @test
     */
    public function canCreateInheritedSubFolderModel()
    {
        $userComments = $this->app->tests->userComments;
        $this->assertEquals(0, $userComments->count());
        $comment = new \Models\Tests\Users\Comment(null, 'Test comment');
        $userComments->save($comment);
        $this->assertEquals(1, $userComments->count());
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
        // Setup
        $users = $this->app->tests->users;
        $userComments = $this->app->tests->userComments;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no comments.
        $this->assertEquals(0, $user->comments->count());

        // Create comment using userComments repo.
        $comment = new \Models\Tests\Users\Comment($user->id, 'Test comment');
        $userComments->save($comment);

        // Check that user now has comment. Need to fetch user fresh from DB to ensure new comment is fetched and 
        // cached empty set isn't used instead.
        $this->assertEquals(1, $users->find($user->id)->comments->count());
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
        // Setup
        $users = $this->app->tests->users;
        $userComments = $this->app->tests->userComments;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no comments.
        $this->assertEquals(0, $user->comments->count());

        // Create new comment and add to User via normal repo call.
        $user->comments->add(new \Models\Tests\Users\Comment($user->id, 'Test comment 1'));
        $this->app->tests->users->save($user);

        // Check that user has now has 1 comment
        $this->assertEquals($user->comments->count(), 1);

        // Create new comment and add to User via active record type call.
        $user->comments->add(new \Models\Tests\Users\Comment($user->id, 'Test comment 2'));
        $user->save();

        // Check that user has now has 2 comments
        $this->assertEquals(2, $user->comments->count());

        // Pull user fresh from DB just to make sure comments were saved on DB.
        $this->assertEquals(2, $users->find($user->id)->comments->count());
    }


    /**
     *  If a User has a collection of Dates related to it using a Via column,
     *  make sure that collection can be replaced.
     *
     *  @test
     */
    public function canReplaceRelatedCollectionByVia()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no stored dates
        $this->assertEquals(0, $user->dates->count());

        // Replace dates associated with User
        $user->dates = $this->app->container(false, [
            new \Models\Tests\Date('Birthday', '10/10/1980'),
            new \Models\Tests\Date('Turned 21', '10/10/2001'),
            new \Models\Tests\Date('Bought First House', '02/14/2008')
        ]);
        $users->save($user);

        // Check that user has now has 3 dates
        $this->assertEquals(3, $user->dates->count());

        // Pull user fresh from DB just to make sure dates were saved on DB.
        $this->assertEquals(3, $users->find($user->id)->dates->count());
    }

    /**
     *  If a User has a collection of Users related to it using a reference table,
     *  make sure that collection can be replaced.
     *
     *  @test
     */
    public function canReplaceRelatedCollectionByRef()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no stored friends
        $this->assertEquals(0, $user->friends->count());

        // Create new list of friends for this User
        $user->friends = $this->app->container(false, [
            new \Models\Tests\User('Suzzy'),
            new \Models\Tests\User('Jeff'),
            new \Models\Tests\User('Randel')
        ]);
        $users->save($user);

        // Check that user has now has 3 dates
        $this->assertEquals(3, $user->friends->count());

        // Pull user fresh from DB just to make sure dates were saved on DB.
        $this->assertEquals(3, $users->find($user->id)->friends->count());

        // Now let's replace the collection again and check everything still works.
        $user->friends = $this->app->container(false, [
            new \Models\Tests\User('Jeff'),
            new \Models\Tests\User('Randel')
        ]);
        $users->save($user);
        $this->assertEquals(2, $users->find($user->id)->friends->count());
        $this->assertEquals('Jeff', $users->find($user->id)->friends->get(0)->name);
    }


    /**
     *  If a User has a collection of related models, ensure one can be deleted.
     *
     *  @test
     */
    public function canDeleteRelatedModelByRef()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no stored friends
        $this->assertEquals(0, $user->friends->count());

        // Create new list of friends for this User
        $user->friends = $this->app->container(false, [
            new \Models\Tests\User('Suzzy'),
            new \Models\Tests\User('Jeff'),
            new \Models\Tests\User('Randel')
        ]);
        $users->save($user);

        // Check that user has now has 3 friends
        $this->assertEquals(3, $user->friends->count());

        // Pull user fresh from DB just to make sure friends were saved on DB.
        $freshUser = $users->find($user->id);
        $this->assertEquals(3, $freshUser->friends->count());

        // Check that the first friend is Suzzy
        $this->assertEquals('Suzzy', $freshUser->friends->get(0)->name);

        // Remove Suzzy from friends list and save. 
        $freshUser->friends->remove(0);
        $users->save($freshUser);
        $this->assertEquals('Jeff', $freshUser->friends->get(0)->name);
    }


    /**
     *  If a User has a collection of Users related to it using a reference table,
     *  make sure that collection can be updated.
     *
     *  @test
     */
    public function canEditRelatedCollectionByRef()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no stored friends
        $this->assertEquals(0, $user->friends->count());

        // Create new list of friends for this User
        $user->friends = $this->app->container(false, [
            new \Models\Tests\User('Suzzy'),
            new \Models\Tests\User('Jeff')
        ]);
        $users->save($user);

        // Check that user has now has 2 dates
        $this->assertEquals(2, $user->friends->count());

        // Pull user fresh from DB just to make sure dates were saved on server.
        $this->assertEquals(2, $users->find($user->id)->friends->count());

        // Add a new Friend
        $user->friends->add(new \Models\Tests\User('Randel'));
        $users->save($user);

         // Check that user has now has 3 dates
        $this->assertEquals(3, $user->friends->count());

        // Pull user fresh from DB just to make sure dates were saved on DB.
        $this->assertEquals(3, $users->find($user->id)->friends->count());
    }


    /**
     *  If a User has a relationship with another singular model,
     *  test that we can set and modify this field.
     *
     *  @test
     */
    public function canEditSingleModelRef()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no stored friends
        $this->assertEquals(NULL, $user->father);

        // Set and create father 
        $dad = new \Models\Tests\User('George');
        $user->father = $dad;
        $users->save($user);

        // Check that dad was set to current User
        $this->assertEquals(get_class($dad), get_class($user->father));
        
        // Pull user fresh from DB just to make sure changes were saved on DB.
        $this->assertEquals(get_class($dad), get_class($users->find($user->id)->father));
    }


    /**
     *  If a User has a relationship with another singular model,
     *  test that we can set and modify this field when the useRefTable setting is active.
     *
     *  @test
     */
    public function canEditSingleModelRefUsesRefTable()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no existing model relationship
        $this->assertEquals($user->mother, NULL);

        // Set and create reference
        $mother = new \Models\Tests\User('Janice');
        $user->mother = $mother;
        $users->save($user);

        // Check that dad was set to current User
        $this->assertEquals(get_class($mother), get_class($user->mother));
        
        // Pull user fresh from DB just to make sure changes were saved on DB.
        $this->assertEquals(get_class($mother), get_class($users->find($user->id)->mother));
    }


    /**
     *  The "relTable" setting is for specifying a custom table name to read from. 
     *  The "mother" attribute uses a relation table to store the single reference to the User's mother. 
     *  The goal here is to create a "mother2" attribute which reads from the same table as "mother" and 
     *  should return the same result. If successful, that means the "relTable" setting is working correctly.
     *
     *  @test
     */
    public function canUseRelTableAttributeOnSingle()
    {
        //$this->app->dbBuilder->reset();

        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no existing model relationship
        $this->assertEquals($user->mother, NULL);

        // Set and create reference
        $mother = new \Models\Tests\User('Janice');
        $user->mother = $mother;
        $users->save($user);
        
        // Pull user fresh from DB just to make sure changes were saved on DB.
        // In the previous step we set the "mother" field, the "mother2" field is set to read 
        // from the same table as "mother", so we should get the result if the "relTable" setting 
        // is working correctly.
        $this->assertEquals(get_class($mother), get_class($users->find($user->id)->mother2));
    }


    /**
     *  The "relTable" setting is for specifying a custom table name to read from. 
     *  Here we are testing that the "friends2" attribute returns the same value as the 
     *  "friends" attribute. This is accomplished by telling "friends2" to use a custom table 
     *  which is specified to be the same one "friends" uses.
     *
     *  @test
     */
    public function canUseRelTableAttributeOnCollection()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob', 'Admin');
        $users->save($user);

        // Check that user has no stored friends
        $this->assertEquals(0, $user->friends->count());

        // Create new list of friends for this User
        $user->friends = $this->app->container(false, [
            new \Models\Tests\User('Suzzy'),
            new \Models\Tests\User('Jeff')
        ]);
        $users->save($user);

        // Check that user has now has 2 relations
        $this->assertEquals(2, $user->friends->count());

        // Pull user fresh from DB just to make sure relations were saved on server.
        $this->assertEquals(2, $users->find($user->id)->friends->count());

        // Add a new Friend using the "friends2" attribute.
        $user->friends2->add(new \Models\Tests\User('Randel'));
        $users->save($user);

         // Check that user has now has 3 relations
        $this->assertEquals(3, $user->friends2->count());

        // Pull user fresh from DB just to make sure relations were saved on DB.
        // Check that both friends and friends2 return the same number of results.
        $this->assertEquals(3, $users->find($user->id)->friends->count());
        $this->assertEquals(3, $users->find($user->id)->friends2->count());
    }


    /**
     *  If a User object is stored in the primary database, we want to make sure 
     *  that user can reference objects located in a secondary DB. 
     *  In this test, the collection is referenced by the "Via" keyword.
     *
     *  @test
     */
    public function canManipulateCollectionInOtherDatabaseByVia()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no stored references
        $this->assertEquals(0, $user->blogposts->count());

        // Set collection of data
        $user->blogposts = $this->app->collection([
            new \Models\Tests\BlogPost('Hello World 1'),
            new \Models\Tests\BlogPost('Hello World 2'),
            new \Models\Tests\BlogPost('Hello World 3')
        ]);
        $users->save($user);

        // Check that user has now has correct # of objects
        $this->assertEquals(3, $user->blogposts->count());

        // Pull user fresh from DB just to make sure references were saved on DB.
        $this->assertEquals(3, $users->find($user->id)->blogposts->count());
    }


    /**
     *  If a User object is stored in the primary database, we want to make sure 
     *  that user can reference objects located in a secondary DB. 
     *  In this test, the collection is referenced by a reference table.
     *
     *  @test
     */
    public function canManipulateCollectionInOtherDatabaseByRefTable()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no stored references
        //echo count($user->articles);
        $this->assertEquals(0, $user->articles->count());

        // Set collection of data
        $user->articles = $this->app->collection([
            new \Models\Tests\Article('My Favorite Books Vol 1'),
            new \Models\Tests\Article('My Favorite Books Vol 2'),
            new \Models\Tests\Article('My Favorite Books Vol 3')
        ]);
        $users->save($user);
        
        // Check that user has now has correct # of objects
        $this->assertEquals(3, $user->articles->count());

        // Pull user fresh from DB just to make sure references were saved on DB.
        $freshUser = $users->find($user->id);
        $this->assertEquals(3, $freshUser->articles->count());

        $this->assertEquals('My Favorite Books Vol 1', $freshUser->articles->get(0)->text);
    }


    /**
     *  Make sure can properly save dates.
     *
     *  @test
     */
    public function datesProperlySavedWithoutManualConversion()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that attribute in question is the default value
        $this->assertEquals('05/02/1982', $user->birthday->format("m/d/Y"));

        // Assign a value to the attribute that has a custom field set 
        $user->birthday = new \DateTime("05/10/2016");
        $users->save($user);

        // Pull user fresh from DB just to make sure references were saved on DB.
        $freshUser = $users->find($user->id);
        $this->assertEquals("05/10/2016", $freshUser->birthday->format("m/d/Y"));
    }


    /**
     *  Make sure a simple attribute that has a custom DB field set 
     *  properly can read from and save to the DB.
     *
     *  @test
     */
    public function simpleAttributeCanHaveCustomDdFieldname()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that attribute in question is null
        $this->assertEquals(NULL, $user->lastModified);

        // Assign a value to the attribute that has a custom field set 
        $user->lastModified = new \DateTime("05/10/2016");
        $users->save($user);

        // Pull user fresh from DB just to make sure references were saved on DB.
        $freshUser = $users->find($user->id);
        $this->assertEquals("05/10/2016", $freshUser->lastModified->format("m/d/Y"));
    }


    /**
     *  Test if custom fieldname can be set for a singular model reference. 
     *  (singular references are normally stored as a field on the table)
     *
     *  @test
     */
    public function canSetCustomFieldnameForSingleModelRef()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that field is null to start
        $this->assertEquals(NULL, $user->grandpa);

        // Set and create ref
        $grandpa = new \Models\Tests\User('GrandPaPa');
        $user->grandpa = $grandpa;
        $users->save($user);

        // Check that ref was set to current User
        $this->assertEquals(get_class($grandpa), get_class($user->grandpa));
        
        // Pull user fresh from DB just to make sure changes were saved on DB.
        $this->assertEquals(get_class($grandpa), get_class($users->find($user->id)->grandpa));
        $this->assertEquals($grandpa->name, $users->find($user->id)->grandpa->name);
    }


    /**
     *  If check if a many-to-many relationship can be used correctly.
     *  
     *  @test
     */
    public function canUseManyToManyRelationOverMultipleDBs()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no stored references
        $this->assertEquals(0, $user->multiAuthorArticles->count());

        // Set collection of data
        $user->multiAuthorArticles = $this->app->collection([
            new \Models\Tests\MultiAuthorArticle('art1'),
            new \Models\Tests\MultiAuthorArticle('art2'),
            new \Models\Tests\MultiAuthorArticle('art3')
        ]);
        $users->save($user);
        
        // Check that user has now has correct # of objects
        $this->assertEquals(3, $user->multiAuthorArticles->count());

        // Pull user fresh from DB just to make sure references were saved on DB.
        $freshUser = $users->find($user->id);
        $this->assertEquals(3, $freshUser->multiAuthorArticles->count());

        // Create 2nd user 
        $user2 = new \Models\Tests\User('Suzzy');
        $user2->multiAuthorArticles->add($user->multiAuthorArticles->off0);
        $users->save($user2);

        // Pull user fresh from DB just to make sure references were saved on DB.
        $freshUser = $users->find($user2->id);
        $this->assertEquals(1, $freshUser->multiAuthorArticles->count());
        $this->assertEquals('art1', $freshUser->multiAuthorArticles->get(0)->text);
    }


    /**
     *  Check that default values work correctly
     *  
     *  @test
     */
    public function canUseDefaultValues()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);
        
        // Grab user fresh from DB, so that default values get populated
        $user = $users->find($user->id);
        
        // Check that user's default attributes got applied correctly
        $this->assertEquals("05/02/1982", $user->birthday->format("m/d/Y"));
        $this->assertEquals("Standard", $user->type);
    }


    /**
     *  Check that fields set to NULL don't change DB.
     *  
     *  @test
     */
    public function nullAttributesDontUpdateDB()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user and assign some attributes
        $user = new \Models\Tests\User('Bob');
        $user->type = "Advanced";
        $user->birthday = new \DateTime("10/10/1981");
        $user->father = new \Models\Tests\User("Jesse");
        $user->friends = $this->app->container(false, [
            new \Models\Tests\User('Suzzy'),
            new \Models\Tests\User('Jeff'),
            new \Models\Tests\User('Randel')
        ]);
        $users->save($user);

        // Grab fresh user from DB
        $user = $users->find($user->id);

        // Check that data was successfully saved to DB
        $this->assertEquals("Advanced", $user->type);
        $this->assertEquals("10/10/1981", $user->birthday->format("m/d/Y"));
        $this->assertEquals("Jesse", $user->father->name);
        $this->assertEquals(3, $user->friends->count());

        // Set the attributes to null on the model, then save. 
        $user->type = null;
        $user->birthday = null;
        $user->father = null;
        $user->friends = null;
        $users->save($user);

        // Grab fresh user from DB
        $user = $users->find($user->id);

        // Check that data still exists in the DB and wasn't replaced by the last save with NULLs
        $this->assertEquals("Advanced", $user->type);
        $this->assertEquals("10/10/1981", $user->birthday->format("m/d/Y"));
        $this->assertEquals("Jesse", $user->father->name);
        $this->assertEquals(3, $user->friends->count());
    }


    /**
     *  In order to clear an attribute in the DB, it needs to be set to FALSE on the model. 
     *  For boolean values, use 0 instead of FALSE.
     *  For collections you need to set to empty collection. 
     *  
     *  @test
     */
    public function falseAttributesSetToNullInDB()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user and assign some attributes
        $user = new \Models\Tests\User('Bob');
        $user->type = "Advanced";
        $user->birthday = new \DateTime("10/10/1981");
        $user->father = new \Models\Tests\User("Jesse");
        $user->friends = $this->app->container(false, [
            new \Models\Tests\User('Suzzy'),
            new \Models\Tests\User('Jeff'),
            new \Models\Tests\User('Randel')
        ]);
        $users->save($user);

        // Grab fresh user from DB
        $user = $users->find($user->id);

        // Check that data was successfully saved to DB
        $this->assertEquals("Advanced", $user->type);
        $this->assertEquals("10/10/1981", $user->birthday->format("m/d/Y"));
        $this->assertEquals("Jesse", $user->father->name);
        $this->assertEquals(3, $user->friends->count());

        // Set the attributes to false on the model, then save. 
        $user->type = false;
        $user->birthday = false;
        $user->father = false;
        $user->friends = $this->app->collection;
        $users->save($user);

        // Grab fresh user from DB
        $user = $users->find($user->id);

        // Check that data was deleted
        $this->assertEquals(null, $user->type);
        $this->assertEquals(null, $user->birthday);
        $this->assertEquals(null, $user->father);
        $this->assertEquals(0, $user->friends->count());
    }


    /**
     *  If an int or datetime field has lock=true attribute, 
     *  ensure old data can't overwrite newer.
     *  @group failing
     *  @test
     */
    public function optimisticLockingWorks()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no stored references
        $this->assertEquals(0, $user->multiAuthorArticles->count());

        // Set collection of data
        $user->multiAuthorArticles = $this->app->collection([
            new \Models\Tests\MultiAuthorArticle('art1'),
            new \Models\Tests\MultiAuthorArticle('art2'),
            new \Models\Tests\MultiAuthorArticle('art3')
        ]);
        $user->multiAuthorArticles->off0->version = 1;
        $users->save($user);

        // Ensure version is set to 1
        $this->assertEquals(1, $users->find($user->id)->multiAuthorArticles->off0->version);
        $this->assertEquals(1, $user->multiAuthorArticles->off0->version);
        $this->assertEquals(0, $user->multiAuthorArticles->off1->version);
        $this->assertEquals(0, $user->multiAuthorArticles->off2->version);

        // Assert that update is possible if version is equal or newer 
        $user->multiAuthorArticles->off0->text = "NewArt1";
        $user->multiAuthorArticles->off0->version = 1; // It should already have a value of 1, just explicitly showing here.
        $users->save($user);

        // Check that text was updated, and that version number was updated
        $this->assertEquals('NewArt1', $users->find($user->id)->multiAuthorArticles->off0->text);
        $this->assertEquals(2, $users->find($user->id)->multiAuthorArticles->off0->version);
        $this->assertEquals(2, $user->multiAuthorArticles->off0->version);

        // Try and change the name of article 1 when the version is older than DB 
        $user->multiAuthorArticles->off0->text = "NewArt2";
        $user->multiAuthorArticles->off0->version = 0;
        $this->assertEquals(0, $user->multiAuthorArticles->off0->version);

        // Do 3nd save
        $lockWorked = false;
        try {
            $users->save($user);
        } catch (\Cora\LockException $e) {
            $lockWorked = true;
        }

        // Assert that update was rejected
        $this->assertEquals(true, $lockWorked);
        $this->assertEquals('NewArt1', $users->find($user->id)->multiAuthorArticles->off0->text);

        // The 3rd save should NOT have increased the version numbers of the articles
        $this->assertEquals(2, $users->find($user->id)->multiAuthorArticles->off0->version);
        $this->assertEquals(1, $users->find($user->id)->multiAuthorArticles->off1->version);

        // The 3rd lock rejected save should not have updated the existing models... 
        $this->assertEquals(0, $user->multiAuthorArticles->off0->version);
        $this->assertEquals('NewArt2', $user->multiAuthorArticles->off0->text);
        $this->assertEquals(1, $user->multiAuthorArticles->off1->version);

        // Assert that update is possible if version is equal or newer 
        $user->multiAuthorArticles->off0->version = 3;
        $users->save($user);
        $this->assertEquals('NewArt2', $users->find($user->id)->multiAuthorArticles->off0->text);
    }

}