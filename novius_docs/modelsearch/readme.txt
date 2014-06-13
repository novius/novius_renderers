This renderer allow user to search among defined models in order to retrieve its ID.

Default model is Model_Page (defined by novius_renderers/config/renderer/modelsearch.config.php).
Models can be add thks to this very same file, by extending it, or thanks to renderer configuration (see config.sample).

The keys sent by the renderer can be chosen or automatically set to :
    "current model prefix" + "_foreign_id" AND "_foreign_model"

eg : for Nos\BlogNews\News\Model_Post, data sent would be "post_foreign_model" and "post_foreign_id".

Then you have to deal with these field thanks to "populate" and "before_save" methods (see config.sample).