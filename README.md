# Cora

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
  
- **A state-of-the-art ORM called AmBlend**
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
- **A data validation system**
- **A pagination system**
- **A wrapper for PHPMailer to assist with sending emails through SMTP**
- **A redirection system for directing user's browsers**
- **An abstraction layer for user input**
- **A flexible Views system**

## Docs
Furthermore, it's the goal of Cora's documentation to 

For documentation (including setup) please see the GitHub pages website here:
http://joebubna.github.io/Cora/

## License

The Cora framework is licensed under the [MIT license](http://opensource.org/licenses/MIT).

