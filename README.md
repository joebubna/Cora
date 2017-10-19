# Cora

## Documentation
The goal of Cora's documentation is to provide developers of all skill levels the knowledge they need to make amazing applications. 

For most developers this will be done via ELI5 (explain it like I'm 5) type explanations and walk-through app building guides. For experienced developers, the reference documentation is meant to give a complete understanding of the tools in question and to hopefully strip away as much of the "magic" as possible. Most developers I talk to are happy to use Black Box style tools most of the time, but when you run into a stag - not knowing how the underlying framework your app is built upon works can be frustrating. A knowledgable developer who understands what's going on is better able to determine the correct solution for their problem.

For documentation (including setup) please see the GitHub pages website here:
http://joebubna.github.io/Cora/

## About Cora

Cora is a flexible MVC PHP framework for rapid app development. It's powered by the belief that designing software should be fun and the complicated (and mundane) stuff should be handled by the framework, allowing the developer to focus on building. Some of the features included in Cora are:

- **A simple routing engine**
  - Allows you to integrate with pre-existing legacy apps.
  - Automatic routing that makes sense and follows class visibility.
   
- **A custom dependency injection container**
  - You can change the dependencies needed by parts of your app without needing to search/replace every "new" declaration.
  - Simplify your code.
  - Structure your app's resources into groups for clarity and group manipulation.
  - Ability to have requests for resources in child containers cascade up through its parents.
  
- **A database access object (DAO)**
  - Adds abstaction layer over databases that allows you to build queries dynamically.
  - In the future will allow you to access multiple database types using the same API as more adaptors are built. (I.E. querying a NoSQL database but using SQL looking code.
  
- **A state-of-the-art ORM called ADM**
  - Uses a Data-Mapper implementation in the form of a Repository-Gateway-Factory pattern.
  - Models are defined using a simple data member array. No special comment tags or weird methods.
  - Models are just regular classes, can be used just like any other class.
  - Models have some "smart" methods that allow them to understand themselves and their relationships to other models based off their definition. This allows some advanced functionality.
  - For people that like the Active-Record format, you can call save() on a model to persist it. This will cause that model to invoke a repository to save itself, which is made possible by the smart logic it inherits.
  - Models get saved recursively and different types of repositories get created as needed. This, more than anything else is the "State-of-the-Art" aspect of the ORM. To understand how powerful this is, you have to see examples, but it allows you to work fluidly with data in your app in a way that feels natural, saves you time, and simplifies your code.
  - Models work seemlessly across multiple databases. I.E. A "User" model could have a plural relationship with a "Transaction" model that is stored in a completely different database and accessing those models works effortlessly.
  - Highly customizable. Models don't have to have the same name as the underlying table/collection that persists them. Model attributes don't have to have the same names as the underlying fields that represent them. Models can be stored on different databases. Relation table names can be customized, etc.
  
- **A database builder tool that will construct all your tables/collections for you based off your model definitions.**
  - This allows the developer to focus on how the app needs to work, and not worry about how to represent complicated relationships in a database.
  - During development, make changes to your models and rebuild your database structure to match in a few seconds.
  
- **An events system**
  - Register listeners for an event.
  - When that event gets fired, any listeners attached get passed an instance of the event and run.
  - The order in which listeners get executed can be customized.
  - Great for things like logging or sending out emails.
  
- **A data validation system**
  - Make sure user submitted data matches expected formats.
  - Can do things like check if a form field was filled out, check if a value is numeric, is an email, etc.
 
- **A flexible Views system**
  - Create one or more templates for your site as needed. 
  - Split up data into dynamic sections as needed (header, sidebar, content, footer, etc).
  - Views can cascade (views within views).
  
- **And More...**
  - A pagination system.
  - A wrapper for PHPMailer to assist with sending emails through SMTP.
  - A redirection system for directing user's browsers.
  - An abstraction layer for user input.
  - ...

## Non-Opinionated

One thing I'd like to stress (especially after seeing that long list of features above) is that almost every pattern, feature, and philosophy in Cora can be ignored and done in a way that makes sense to you.

- Want to utilize Cora's automatic routing, but not any of the other features available? Go for it.
- Want to use the database access object, but not the ORM? Go for it.
- Want to not use the Dependency Injection Container? Go for it.
- Want to use some other View system to display content to users? Go for it.
- Want to put all your logic in Listeners and barely use Controllers? Go for it.
- Want add a new layer of logic between your Controllers and your Models/Database layer? Go for it.
- Want to call the classes in your app Classes rather than models? Go for it.
- Want to use the ORM but with an existing database or one you create by hand rather than use the Database Builder? Go for it.
- Want to add methods to your models that interact directly with the persistence layer (ala Active-Record)? Go for it.

Cora was originally built to be integrated into an existing legacy app, and generally tries to be as flexible as possible. Just because certain approaches may be suggested or presented in the documentation, don't feel like you have to do things the same way. Build your app in the way that makes sense for you.

## License

The Cora framework is licensed under the [MIT license](http://opensource.org/licenses/MIT).

