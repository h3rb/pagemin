# pagemin
Pagemin ("page-eee-min" or "paj-eh-min" similar to pokemon) is the minimal core of the Page framework

Page framework: http://github.com/h3rb/page

You'll need a schema, or you can create your own based on the conventions set forth in Page. There is also the one in the Page repo, https://github.com/h3rb/page/blob/master/docs/Page_AuthDB.sql

Pagemin comes without an authentication system, though you can view core/Auth.php for where you would add such logic.  It provides support for models, databases, and the Page object.  It is from this minimal starting place that you can begin to build out an application that integrates PHP with whatever front-end world you want to create (Angular, REACT, jQuery, etc.)

It also doesn't have all of the sample application cruft from the Page repo.   A nice to place to start, unless you are building an API, in which case you may wish to take a look at http://github.com/h3rb/papi
