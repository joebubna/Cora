<?php
namespace Tests\Cora;

use \PHPUnit\Framework\Attributes\Test;

class ADMTest extends \Cora\App\TestCase
{   
    /**
     *  Check that it's possible to create a simple model located that doesn't inherit from another model.
     *  
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  Test that a "relName" definition correctly causes attributes on two 
     *  different models to read from the same relation table.
     *  
     */
    #[Test]
    public function canSetRelationshipNameToLinkDataBetweenModels()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob47');
        $users->save($user);

        // Check that user has no stored references
        $this->assertEquals(0, $user->writings->count());

        // Set and create ref
        $user->writings = $this->app->collection([
            new \Models\Tests\MultiArticle('My Favorite Books Vol 1'),
            new \Models\Tests\MultiArticle('My Favorite Books Vol 2'),
            new \Models\Tests\MultiArticle('My Favorite Books Vol 3')
        ]);
        $users->save($user);

        // Check that user has now has correct # of objects
        $this->assertEquals(3, $user->writings->count());

        // Pull user fresh from DB just to make sure references were saved on DB.
        $freshUser = $users->find($user->id);
        $this->assertEquals(3, $freshUser->writings->count());
        $this->assertEquals('My Favorite Books Vol 1', $freshUser->writings->get(0)->name);

        // Grab the first article
        $article = $user->writings->get(0);

        // Check that grabbing the authors from the Article side of the relationship reads 
        // from the same relation table and thus returns the author. 
        $this->assertEquals(1, $article->authors->count());
    }


    /**
     *  Test that a "relThis" and "relThat" definition can be used to change the field names 
     *  on a relation table. Identifying which column refers to the currently being read object,
     *  and which refers to the other.
     *
     *  
     */
    #[Test]
    public function canSetRelationTableFieldNames()
    {
        // Setup
        $users = $this->app->tests->users;

        // Create user 
        $user = new \Models\Tests\User('Bob');
        $users->save($user);

        // Check that user has no stored references
        $this->assertEquals(0, $user->contacts->count());

        // Set and create ref
        $user->contacts = $this->app->collection([
            new \Models\Tests\User('Janice'),
            new \Models\Tests\User('Oscar'),
            new \Models\Tests\User('Jim')
        ]);
        $users->save($user);

        // Check that user has now has correct # of objects
        $this->assertEquals(3, $user->contacts->count());

        // Pull user fresh from DB just to make sure references were saved on DB.
        $freshUser = $users->find($user->id);
        $this->assertEquals(3, $freshUser->contacts->count());

        $this->assertEquals('Janice', $freshUser->contacts->get(0)->name);
    }


    /**
     *  If check if a many-to-many relationship can be used correctly.
     *  
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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
     *  
     */
    #[Test]
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


    /**
     *  If we create a new Model using just the ID number, without fetching it from a repository,
     *  can we access related models fetched using the "via" keyword on it?
     *  
     *  
     */
    #[Test]
     public function canAccessRelatedModelsFromViaOnNewObject()
     {
        $this->app->dbBuilder->reset();

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
        $users->save($user);

        // Check that user has now has 1 comment
        $this->assertEquals($user->comments->count(), 1);
        
        // Create new comment and add to User via active record type call.
        $user->comments->add(new \Models\Tests\Users\Comment($user->id, 'Test comment 2'));
        $user->save();
        
        // Check that user has now has 2 comments
        $this->assertEquals(2, $user->comments->count());

        // Create a new user object from scratch using just the ID number 
        $user2 = new \Models\Tests\User();
        $user2->id = $user->id;

        // Check that access works the same on this new model
        $this->assertEquals(2, $user2->comments->count());
     }


   /**
    *   Checks that model constraints added via the model_constraints method correctly apply
    *   to a collection.
    *
    *   
    */
    #[Test]
    public function canLimitCollectionByModelConstraints()
    {
        // Setup
        $users = $this->app->users;
        $inactiveUsers = $this->app->tests->inactiveUsers;

        // Check that user has no comments.
        $this->assertEquals(1001, $users->count());

        // Check that user has no comments.
        $this->assertEquals(10, $inactiveUsers->count());
    }


   /**
    *   Checks that model constraints via the model_constraints method combined with 
    *   a LoadMap from the model_loadMap method can be used in conjunction to efficiently 
    *   load models.
    * 
    *   
    */
    #[Test]
    public function canDefineLoadMapOnModelForEfficientLoading()
    {
        // Setup
        $userRepo = $this->app->users;
        $inactiveUserRepo = $this->app->repository(\Models\Tests\InactiveUserWithRole::class);

        $users = $userRepo->findBy('status', 'Inactive');
        $inactiveUsers = $inactiveUserRepo->findAll();

        // Check that the correct amount of users have been fetched for both model groups.
        $this->assertEquals(10, $users->count());
        $this->assertEquals(10, $inactiveUsers->count());

        // Turn off dynamic loading on all models.
        foreach ($users as $user) {
          $user->model_dynamicOff = true;
        }
        foreach ($inactiveUsers as $user) {
          $user->model_dynamicOff = true;
        }

        // Verify that the regular user object does not have the Role data loaded, 
        // but that the LoadMapped version does.
        // Also verifies that the Role data was populated from the initial query 
        // because dynamic loading was turned off.
        $this->assertEquals(true, is_int($users[0]->primaryRole));
        $this->assertEquals(true, is_object($inactiveUsers[0]->primaryRole));

        $this->assertEquals('User', $inactiveUsers[0]->primaryRole->name);
    }


    /**
    *   Abstract relationships between models are ones where there isn't an explicitly defined
    *   ID => ID connection. An explicit connection is something like a "parent" field with an ID number,
    *   a relation table defining Model A is related to Model B, etc. An abstract relationship is something 
    *   like "theSameAge" where the result is all users within 5 years of age to the person in question. 
    *   Representing explicit ID => ID connections for every person in the database to define this relationship 
    *   would be rediculous and non-feasible. 
    *
    *   The "using" definition allows you to specify a method that will define an abstract relationship.
    *
    *   
    */
    #[Test]
    public function canGrabRelatedModelsFromAbstractRelationship()
    {
        // Setup
        $this->app->dbBuilder->reset();
        $users = $this->app->tests->users;

        // Check that the test users table is empty
        $this->assertEquals(0, $users->count());
        
        $testUsers = new \Cora\Collection([
            new \Models\Tests\User('Bob1', 'Adult'),
            new \Models\Tests\User('Jimmy', 'Child'),
            new \Models\Tests\User('Jenine', 'Adult'),
            new \Models\Tests\User('Jeff', 'Adult'),
            new \Models\Tests\User('Matt', 'Child'),
            new \Models\Tests\User('Sarah', 'Child'),
            new \Models\Tests\User('Kevin', 'Adult')
        ]);

        $users->save($testUsers);

        // Check that the users were saved
        $this->assertEquals(7, $users->count());

        // Grab Jenine
        $user = $users->find(3);

        // Ensure we have jenine
        $this->assertEquals('Jenine', $user->name);

        // Check that the abstract relationship to other "adults" works
        $this->assertEquals(3, $user->sameType->count());
    }


    /**
    *   Check that models which utilize abstract models can be saved.
    *
    *   
    */
    #[Test]
    public function canSaveModelUtilizingAbstractRelationship()
    {
        // Setup
        $users = $this->app->tests->users;

        // Grab Jenine
        $user = $users->find(3);

        // Ensure we have jenine
        $this->assertEquals('Jenine', $user->name);

        // Check that the abstract relationship to other "adults" works
        $this->assertEquals(3, $user->sameType->count());

        // Change Bob1 to child
        $user->sameType[0]->type = 'Child';

        // Save user
        $users->save($user);

        // Re-Grab Jenine
        $user = $users->find(3);

        // Check that the abstract relationship to other "adults" works
        $this->assertEquals(2, $user->sameType->count());
    }


    /**
     *  Sometimes you might not want to fetch all the related models for a relationship.
     *  For example you may want to paginate or search. This checks that you can pass a closure
     *  to a plural relationship for customizing the data that gets returned.
     *
     *  
     */
    #[Test]
    public function canPassClosureToCustomizeFetchingOfRelatedModels()
    {
        // Setup
        $users = $this->app->tests->users;

        // Change Bob1 back to Adult from previous test
        $bob = $users->find(1);
        $bob->type = 'Adult';
        $users->save($bob);

        // Grab Jenine
        $user = $users->find(3);

        // Ensure we have jenine
        $this->assertEquals('Jenine', $user->name);

        // Check that the abstract relationship to other "adults" works
        $this->assertEquals(3, $user->sameType->count());

        // Grab adults named either Jeff or Kevin
        $this->assertEquals(2, $user->sameType(function($query) {
          $query->in('name', ['Jeff', 'Kevin']);
          return $query;
        })->count());
    }


    /**
     *  Check that you can pass a closure even for a single model relationship
     *
     *  
     */
    #[Test]
    public function canPassClosureToCustomizeFetchingOfRelatedModel()
    {
        // Setup
        $users = $this->app->tests->users;

        // Grab 4th user from db. Should be "Jeff"
        $user = $users->find(4);

        // Ensure we have jenine
        $this->assertEquals('Jeff', $user->name);

        // Verify that we can override the "father" query
        $this->assertEquals("Jenine", $user->father(function($query) {
          return $query->custom("
            SELECT * FROM tests_users WHERE id = 3
          ");
        })->name);
    }


    /**
     *  Sometimes you might not want to fetch all the related models for a relationship.
     *  For example you may want to paginate or search. This checks that you can pass a closure
     *  to a plural relationship for customizing the data that gets returned.
     * 
     *  This test checks that you can pass arguments to the closure.
     *
     *  
     */
    #[Test]
    public function canPassClosureArguments()
    {
        // Setup
        $users = $this->app->tests->users;

        // Change Bob1 back to Adult from previous test
        $bob = $users->find(1);
        $bob->type = 'Adult';
        $users->save($bob);

        // Grab Jenine
        $user = $users->find(3);

        // Ensure we have jenine
        $this->assertEquals('Jenine', $user->name);

        // Check that the abstract relationship to other "adults" works
        $this->assertEquals(3, $user->sameType->count());

        // Grab adults named either Jeff or Kevin
        $this->assertEquals(2, $user->sameType(function($query, $names) {
          $query->in('name', $names);
          return $query;
        }, [['Jeff', 'Kevin']])->count());
    }


    /**
    *   Check that models which utilize abstract models can be saved.
    *
    *   
    */
    public function canSaveModelsUtilizingAbstractRelationshipToDifferentModel()
    {
        // Setup
        $users = $this->app->tests->users;

        // Grab Jenine
        $user = $users->find(3);

        // Ensure we have jenine
        $this->assertEquals('Jenine', $user->name);

        // Check that the abstract relationship to other "adults" works
        $this->assertEquals(2, $user->multiAbstract->count());

        // Change Admin to Admin2
        $user->multiAbstract[1]->name = 'Admin2';
        
        // Save user
        $users->save($user);

        // Re-Grab Jenine
        $user = $users->find(3);

        // Check that the abstract relationship to other "adults" works
        $this->assertEquals('Admin2', $user->multiAbstract[1]->name);
    }


    /**
     *  Check that you can perform attribute mapping when interacting with a repository.
     * 
     *  This allows you to map from an associative array to model attributes which are named differently.
     *  This is necessary if you have data from a database that doesn't have the correct 
     *  column names for what a model is expecting. For example: If you need to do some sort 
     *  of:
     *    SELECT users.id, users.name, roles.id as 'role_id', roles.name as 'role_name' FROM users, roles
     * 
     *  If the "Role" model expects an array offset named "name", then you'll run into a problem.
     *  By mapping "role_name" => "name" you can solve the issue.
     *  
     *  
     */
    #[Test]
    public function canLoadMapOffsetsToAttributesUsingRepository()
    {
      $this->app->dbBuilder->reset();

      // Grab users repo
      $users = $this->app->tests->users;

      // Create new user named Bob with type 'SuperRadDude'
      $user = new \Models\Tests\User('Bob', 'SuperRadDude');
      $users->save($user);

      // Create a basic LoadMap
      // State that the 'type' field should be mapped to the 'name' attribute on the model.
      // Inversely let's state that the 'name' field should be mapped tot he 'type' attribute.
      $loadMap = new \Cora\Adm\LoadMap([
        'type' => 'name',
        'name' => 'type'
      ]);

      // Grab Bob fresh.
      // (No need to modify the query at all for this example)
      $bob = $users->findOne(function($query) {
        return $query;
      }, false, $loadMap);

      // Ensure 'type' got inversed by the mapping
      $this->assertEquals('Bob', $bob->type);

      // Check that Bob's 'name' is "SuperRadDude" per the mapping
      $this->assertEquals('SuperRadDude', $bob->name);
    }


    /**
     *  Check that you can specify relationships to load and pass them a LoadMap when interacting with a repository.
     * 
     *  In the previous test we explained offset to attribute mapping using the following query as an example: 
     *  SELECT users.id, users.name, roles.id as 'role_id', roles.name as 'role_name' FROM users, roles
     * 
     *  In this test we'll actually run a query like that and we'll use the Role data to populate a related 
     *  Role model without the need for additional queries.
     * 
     *  For example, pretend you run some sort of ancentry website:
     *  Say you want to grab a list of users and you want to iterate over them grabbing
     *  the name of each user's father.
     * 
     *  In most ORMs, each iteration would result in a new query of the database. So if you 
     *  have 100 users, you'll end up doing 101 queries (one to fetch the users, then 100 to fetch
     *  the father of each user). This is not ideal and one of the biggest drawbacks to ORMs.
     * 
     *  Using the feature below, you can intelligently grab and populate all the data you need 
     *  in one query.
     *  
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsUsingRepository()
    {
      ///////////////////////////////
      // DB setup for test
      ///////////////////////////////

      // Grab users repo
      $users = $this->app->tests->users;

      // Grab the user Bob we created in the previous test
      $user = $users->find(1);

      // Check that user has no father
      $this->assertEquals(NULL, $user->father);

      // Set and create father 
      $dad = new \Models\Tests\User('George');
      $user->father = $dad;
      $users->save($user);


      ///////////////////////////////
      // Verify that with dynamic loading turned off, that no data about a father 
      // relationship will be loaded.
      ///////////////////////////////

      // Grab Bob fresh using normal ID find
      $bobNormal = $users->find($user->id);

      // Turn off dynamic loading
      $bobNormal->model_dynamicOff = true;

      // Ensure that no data for the father is loaded or gets dynamically loaded when we try and access it
      // The value for the father attribute should be an ID number, which since we created the father 
      // 2nd should be 2.
      $this->assertEquals(2, $bobNormal->father);


      ///////////////////////////////
      // Verify that using a custom JOIN query and a LoadMap, the father data can be loaded
      // as part of the original query without the need for dynamic loading
      ///////////////////////////////

      // Create LoadMap for a custom query we will do
      // State that we want to load the "father" relationship and that 'father_id' and 
      // 'father_name' fields should be mapped to the id and name attributes on the father 
      // user model.
      $loadMap = new \Cora\Adm\LoadMap([
        'name' => 'type'
      ], [
        'father' => new \Cora\Adm\LoadMap([
          'father_id' => 'id',
          'father_name' => 'name'
        ])
      ]);

      // Grab Bob fresh.
      // Define a custom query to execute that joins the Father data we need
      // Then pass the loadMap we created so it knows how to populate the Father model.
      $bob = $users->findOne(function($query, $id) {
        $query->where('tests_users.id', $id)
              ->join('tests_users usersB', [['tests_users.father', '=', 'usersB.id']], 'LEFT')
              ->select(['tests_users.*', 'usersB.id as father_id', 'usersB.name as father_name']);
        return $query;
      }, $user->id, $loadMap);

      // Ensure we have Bob
      $this->assertEquals('Bob', $bob->name);

      // Ensure the mapping from name => type worked
      $this->assertEquals('Bob', $bob->type);

      // Turn off dynamic loading
      $bob->model_dynamicOff = true;

      // Ensure Bob's father was loaded non-dynamically using the data 
      // from the original closure query above.
      $this->assertEquals('George', $bob->father->name);
    }


    /**
     *  Ensure that we can use LoadMaps with plural relationships when using the 
     *  Active-Record-like functionality of models. (vs. directly interacting with a repository)
     * 
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsUsingModelNoDynamic()
    {
      ///////////////////////////////
      // DB setup for test
      ///////////////////////////////

      // Grab users repo
      $users = $this->app->tests->users;

      // Grab Bob
      $user = $users->find(1);

      // Give bob some friends
      $user->friends = new \Cora\Collection([
        new \Models\Tests\User('Suzzy'),
        new \Models\Tests\User('Jeff'),
        new \Models\Tests\User('Randel')
      ]);

      // Give those friends some fathers
      $user->friends[0]->father = new \Models\Tests\User('Dad0');
      $user->friends[1]->father = new \Models\Tests\User('Dad1');
      $user->friends[2]->father = new \Models\Tests\User('Dad2');
      $users->save($user);

      ///////////////////////////////
      // Verify that using a custom JOIN query and a LoadMap, the father data can be loaded
      // as part of the original query without the need for dynamic loading
      ///////////////////////////////

      // Create LoadMap for a custom query we will do
      // State that we want to load the "father" relationship and that 'father_id' and 
      // 'father_name' fields should be mapped to the id and name attributes on the father 
      // user model.
      $loadMap = new \Cora\Adm\LoadMap([], [
        'father' => new \Cora\Adm\LoadMap([
          'father_id' => 'id',
          'father_name' => 'name'
        ])
      ]);

      // Grab George fresh.
      // Define a custom query to execute that joins the Father data we need
      // Then pass the loadMap we created so it knows how to populate the Father model.
      $bobsFriends = $user->friends(function($query) {
        $query->join('tests_users usersA', [['usersA.id', '=', 'user2']], 'LEFT')
              ->join('tests_users usersB', [['usersA.father', '=', 'usersB.id']], 'LEFT')
              ->select(['usersB.id as father_id', 'usersB.name as father_name']);
        return $query;
      }, false, $loadMap);

      // Ensure we have the right amount of friends
      $this->assertEquals(3, $bobsFriends->count());

      // Turn off dynamic loading on one of the friends
      $user->friends[1]->model_dynamicOff = true;

      // Ensure that the father of that friend was loaded and populated without any dynamic queries
      $this->assertEquals('Dad1', $user->friends[1]->father->name);
    }


    /**
     *  Checks that just specifying the relationships you want to initially load work 
     *  without also giving an attribute mapping array or a recursive LoadMap.
     * 
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsNoSubLoadMap()
    {
      // Grab users repo
      $users = $this->app->tests->users;

      // Create a simple LoadMap
      // For this we will just specify which relationships we want pre-loaded
      $loadMap = new \Cora\Adm\LoadMap([], [
        'father' => true,
        'friends' => true
      ]);

      // Grab Bob (again), giving the loadMap
      $user = $users->findOne(function($query, $id) {
        return $query->where('id', $id);
      }, 1, $loadMap);

      // Turn off dynamic loading
      $user->model_dynamicOff = true;

      // Ensure we have the right amount of friends
      $this->assertEquals(3, $user->friends->count());

      // Ensure that the father of that friend was loaded and populated without any dynamic queries
      $this->assertEquals('George', $user->father->name);
    }


    /**
     *  Check that you can specify an onLoad function to run using LoadMaps
     *  
     *  
     */
    #[Test]
    public function canLoadMapAnOnLoadFunction()
    {
      // Grab users repo
      $users = $this->app->tests->users;

      // Create LoadMap that passes a closure as 4th argument and params as the 5th
      $loadMap = new \Cora\Adm\LoadMap([
          'name' => 'type'
        ], [
          'father' => new \Cora\Adm\LoadMap([
            'father_id' => 'id',
            'father_name' => 'name'
          ])
        ],
        false,
        function($model, $str1, $str2) {
          $model->lastName = $str1.$str2;
        },
        ['foo', 'BAR']
      );

      // Grab user using the LoadMap
      $user = $users->findOne(function($query, $id) {
        return $query->where('id', $id);
      }, 1, $loadMap);

      // Ensure we have Bob
      $this->assertEquals('Bob', $user->name);

      // Ensure the mapping from name => type worked
      $this->assertEquals('Bob', $user->type);

      // Ensure that Bob's lastname was set using the onLoad closure
      $this->assertEquals('fooBAR', $user->lastName);
    }


    /**
     *  This test ends up verifying three things:
     *    1. Ensure that multi-level nested load maps will work.
     *    2. Ensure that Fetch Data booleans will cause a relationship to be fetched even if not in the original query.
     *    3. Ensure that an empty model will be returned if specified relationship is null
     *  WHile using a REPOSITORY as starting point.
     * 
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsNestedUsingRepo()
    {
      ///////////////////////////////
      // DB setup for test
      ///////////////////////////////

      // Grab users repo
      $users = $this->app->tests->users;

      // Set and create Zach and his Dad
      $zach = new \Models\Tests\User('Zach');
      $zedd = new \Models\Tests\User('Zedd');
      $zach->father = $zedd;
      $users->save($zach);


      // Create LoadMap for a custom query we will do
      // State that we want to load the "father" relationship and define the mappings from the 
      // query to the father model. Also state that we want to load the father of the father, 
      // but set it's third argument to TRUE, meaning it needs to be loaded via a new query.
      $loadMap = new \Cora\Adm\LoadMap([], [
        'father' => new \Cora\Adm\LoadMap([
          'father_id' => 'id',
          'father_name' => 'name',
          'father_father' => 'father'
        ], [
          'father' => new \Cora\Adm\LoadMap([],[], true)
        ])
      ]);

      // Define a custom query to execute that joins the Father data we need
      // Then pass the loadMap we created so it knows how to populate the Father model.
      $user = $users->findOne(function($query, $id) {
        $query->where('tests_users.id', $id)
              ->join('tests_users usersB', [['tests_users.father', '=', 'usersB.id']], 'LEFT')
              ->select([
                'tests_users.*', 
                'usersB.id as father_id', 
                'usersB.name as father_name', 
                'usersB.father as father_father'
              ]);
        return $query;
      }, $zach->id, $loadMap);

      // Ensure we have the correct user
      $this->assertEquals('Zach', $user->name);

      // Turn off dynamic loading
      $user->model_dynamicOff = true;

      // Ensure Zach's father was loaded non-dynamically using the data 
      // from the original closure query above.
      $this->assertEquals('Zedd', $user->father->name);

      // Zedd has no defined father. However, because we included the embedded father in 
      // the LoadMap, the ORM should have returned an empty User model to avoid null errors.
      $this->assertEquals('', $user->father->father->name);
    }


    /**
     *  This test ends up verifying two things:
     *    1. Ensure that Fetch Data booleans will cause a relationship to be fetched even if not in the original query.
     *    2. Ensure that an empty model will be returned if specified relationship is null
     *  WHile using a MODEL as starting point.
     *  
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsNestedUsingModel()
    {
      ///////////////////////////////
      // DB setup for test
      ///////////////////////////////

      // Grab users repo
      $users = $this->app->tests->users;

      // Set and create Zach and his Dad
      $user = new \Models\Tests\User('Adam');
      $alex = new \Models\Tests\User('Alex');
      $user->father = $alex;
      $users->save($user);


      // Create LoadMap for a custom query we will do
      // State that we want to load the "father" relationship and define the mappings from the 
      // query to the father model. Also state that we want to load the father of the father, 
      // but set it's third argument to TRUE, meaning it needs to be loaded via a new query.
      $loadMap = new \Cora\Adm\LoadMap([], [
        'father' => true
        //'father' => new \Cora\Adm\LoadMap([],[], true)
      ]);

      // Define a custom query to execute that joins the Father data we need
      // Then pass the loadMap we created so it knows how to populate the Father model.
      // Keep in mind here that "father" is already an object on the User model because we created 
      // it above - this is relevant as it changes possible execution flow.
      $user->father(function($query) {
        return $query;
      }, false, $loadMap);

      // Ensure we have the correct user
      $this->assertEquals('Adam', $user->name);

      // Turn off dynamic loading
      $user->model_dynamicOff = true;

      // Ensure father was loaded non-dynamically using the data 
      // from the original closure query above.
      $this->assertEquals('Alex', $user->father->name);

      // Alex has no defined father. However, because we included the embedded father in 
      // the LoadMap, the ORM should have returned an empty User model to avoid null errors.
      $this->assertEquals('', $user->father->father->name);


      /**
       *  We also need to test the same thing using a fresh User object which doesn't have 
       *  Father as an object
       */

      // Fetch a fresh version of the user where the Father isn't already an object
      $user = $users->find($user->id);

      // Fetch the father with the previous created LoadMap
      $user->father(function($query) {
        return $query;
      }, false, $loadMap);

       // Ensure father was loaded non-dynamically using the data 
      // from the original closure query above.
      $this->assertEquals('Alex', $user->father->name);

      // Alex has no defined father. However, because we included the embedded father in 
      // the LoadMap, the ORM should have returned an empty User model to avoid null errors.
      $this->assertEquals('', $user->father->father->name);
    }


    /**
     *  Ensure that fetching a Collection from a model with a loadMap works.
     *  In this case, the loadMapped relationship should be present.
     *  
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsNestedUsingModelPluralExists()
    {
      // Grab users repo
      $users = $this->app->tests->users;
      $user = new \Models\Tests\User('Adam');
      $user->comments = $this->app->container(false, [
        new \Models\Tests\Users\Comment($user, 'Test Comment 1'),
        new \Models\Tests\Users\Comment($user, 'Test Comment 2'),
        new \Models\Tests\Users\Comment($user, 'Test Comment 3')
      ]);
      $users->save($user);

      // Fetch new
      $user = $users->find($user->id);

      // Create LoadMap
      $loadMap = new \Cora\Adm\LoadMap([], [
        //'madeBy' => true
        'madeBy' => new \Cora\Adm\LoadMap([
          'madeBy' => '!madeBy'
        ], [], true)
      ]);

      // Define a custom query to execute that joins the Father data we need
      // Then pass the loadMap we created so it knows how to populate the Father model.
      // Keep in mind here that "father" is already an object on the User model because we created 
      // it above - this is relevant as it changes possible execution flow.
      $user->comments(function($query) {
        return $query;
      }, false, $loadMap);

      // Ensure we have the right amount of friends
      $this->assertEquals(3, $user->comments->count());

      // Turn off dynamic loading on one of the friends
      $user->comments[1]->model_dynamicOff = true;

      // Ensure that the father of that friend was loaded and populated without any dynamic queries
      $this->assertEquals('Adam', $user->comments[1]->madeBy->name);
    }


    /**
     *  Ensure that "model_extends" attribute works on models.
     *  If trying to fetch a data attribute that does NOT exist on the model, but the model "extends"
     *  (not using PHP extension, but rather an ADM feature) a diff model, check for that attribute on the
     *  parent model.
     */
    #[Test]
    public function canExtendModelWithAdmFeature()
    {
      // Grab users repo
      $users = $this->app->tests->users;
      $user = new \Models\Tests\User('Adam');
      $user->comments = $this->app->container(false, [
        new \Models\Tests\Users\Comment($user, 'Extend Comment 1'),
        new \Models\Tests\Users\Comment($user, 'Extend Comment 2'),
        new \Models\Tests\Users\Comment($user, 'Extend Comment 3')
      ]);
      $users->save($user);

      // Fetch new
      $user = $users->find($user->id);

      // Ensure we have the right amount of comments
      $this->assertEquals(3, $user->comments->count());
      $this->assertEquals('Extend Comment 2', $user->comments[1]->text);

      // Get ID of a comment
      $cID = $user->comments[1]->id;

      // Create LoadMap
      // $loadMap = new \Cora\Adm\LoadMap([], [
      //   //'madeBy' => true
      //   'madeBy' => new \Cora\Adm\LoadMap([
      //     'madeBy' => '!madeBy'
      //   ], [], true)
      // ]);

      $comments = $this->app->tests->userCommentsExtend;
      $comment = $comments->find($cID);

      // Ensure that the father of that friend was loaded and populated without any dynamic queries
      $this->assertEquals('Adam', $comment->name);
    }


    /**
     *  Ensure that fetching a Collection from a model with a loadMap works.
     *  In this case, the loadMapped relationship should be NOT present.
     *  
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsNestedUsingModelPluralNotExists()
    {
      // Grab users repo
      $users = $this->app->tests->users;
      $user = new \Models\Tests\User('Jennifer');
      $users->save($user);

      // Create LoadMap
      $loadMap = new \Cora\Adm\LoadMap([], [
        'madeBy' => new \Cora\Adm\LoadMap([
          'madeBy' => '!madeBy'
        ], [], true)
      ]);

      // Define a custom query to execute that joins the Father data we need
      // Then pass the loadMap we created so it knows how to populate the Father model.
      // Keep in mind here that "father" is already an object on the User model because we created 
      // it above - this is relevant as it changes possible execution flow.
      $user->comments(function($query) {
        return $query;
      }, false, $loadMap);

      // Ensure we have the right amount of friends
      $this->assertEquals(0, $user->comments->count());
    }


    /**
     *  Ensure that fetching a Collection from a model with a loadMap works.
     *  In this case, the loadMapped relationship should be NOT present.
     *  
     *  
     */
    #[Test]
    public function canLoadMapRelationshipsNestedUsingRepoPluralNotExists()
    {
      // Grab users repo
      $users = $this->app->tests->users;
      $user = new \Models\Tests\User('Jennifer');
      $users->save($user);

      // Create LoadMap
      $loadMap = new \Cora\Adm\LoadMap([], [
        'comments' => new \Cora\Adm\LoadMap([], [
          'madeBy' => true
        ], true)
      ]);

      // Fetch user using loadMap
      $user = $users->findOne(function($query, $id) {
        return $query->where('id', $id);
      }, $user->id, $loadMap);

      // Ensure we have the right amount of friends
      $this->assertEquals(0, $user->comments->count());
    }

} // END TEST