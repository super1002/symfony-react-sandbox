Symfony React Sandbox
=====================

This sandbox provides an example of usage of [ReactBundle](https://github.com/limenius/ReactBundle) with server and client-side React rendering (universal/isomorphical) and its integration with a Webpack setup.

It is also a fully functional Symfony application that you can use as skeleton for new projects.

It has three main areas of interest:

* The server-side code under `src/` and `app/config` configuration.
* The JavaScript and CSS (SCSS) code under `client/`.
* The Webpack configuration for client and server-side rendering at `webpack.config.js` and `webpack.config.serverside.js`.

Note that you won't need to run an external node server to do server-side renering, as we are using [PhpExecJs](https://github.com/nacmartin/phpexecjs).


How to run it
=============

Requirements: you need a recent version of node, like `v5.5.0`, and Webpack installed (you can install it with `npm install -g webpack`.

    git clone git://github.com/Limenius/symfony-react-sandbox.git
    composer install
    npm install

And then, run a live server with Webpack hot-reloading of assets:

* Building the server-side react Webpack bundle.
    
    webpack --config webpack.config.serverside.js --watch

* And, In a different terminal/screen/tmux, the hot-reloading webpack server for the client assets:

    webpack-dev-server --progress --colors --config webpack.config.js

* Also, you may want to run the Symfony server:

    bin/console symfony:server:run

After this, visit [http://127.0.0.1:8000](http://127.0.0.1:8000).


Why Webpack?
===========

Webpack is used to generate two separate JavaScript bundles (that share some code). One is meant for its inclusion as context for the server-side rendering. The second will caontain your client-side frontend code. Given this, we can write Twig code to render react components like, for instance:

    {{ react_component('RecipesApp', {'props': props}) }}

And it will be rendered both client and server-side.

We have provided what we think are sensible defaults for both bundles, and also for the package.json dependencies, like bootstrap and such. Feel free to adapt them to your needs.

Please note that if you are copying `webpack.config.js` or `webpack.config.server.js` to your project it is very likely that you will need also `.babelrc` to have Babel presets (used to transform React JSX and modern JavaScript to plain old JavaScript)

Why Server-Side rendering?
==========================

If you enable server-side rendering along with client-side rendering of components (this is the default) your React components will be rendered directly as HTML by Twig and then, when the client-side code is run, React will identify the already rendered HTML and it won't render it again until is needed. Instead, it will silently take control over it and re-render it only when it is needed.

This is vital for some applications for SEO purposes, but also is great for quick page-loads and to provide the content to users with JavaScript disabled (if there is any left, but it is a nice-to-have).

You can configure ReactBundle to have server-side, client-side or both. See the bundle documentation for more information.

How it works
============

When you render a React Component in a Twig template with `{{ react_component('RecipesApp', {'props': props}) }}` with client and server-side rendering enabled (this is the default setting), ReactBundle will render a `<div>` that will serve as container of the component.

Inside of it, the bundle will place all the HTML code that results of evaluating your component. It will do so by calling `PhpExecJs` and retrieving the outcome.

When your client-side JavaScript runs, React will find this `<div>` tag and will recognize it as the result of rendering a component. It won't render it again (unless the evaluation of your client-side code differs), but it will take control of it, and, depending on the actions performes by the user, it will re-render it dynamically.

Walkthrough
===========

We have set-up a simple application. A recipes page. In the actions of the controller under `src/AppBundle/Controller/RecipeController.php` you will find two types of actions. 

### Actions that render Twig templates.

These actions retrieve the recipes that will be shown in the page and pass them as a JSON string to template.

In the Twig templates under `/app/Resouces/views/recipe/` you will find templates with code like tthis one:

    {{ react_component('RecipesApp', {'props': props}) }}

This Twig function, provided by [React Bundle](https://github.com/limenius/ReactBundle), will render the React component `RecipesApp` in server and client modes.

### Actions that render JSON responses.

These actions act as an API and will be used by the client-side React code to retrieve data as needed when navigating to other pages without reloading the pages.

To simplify things, we don't use FOSRestBundle here, but feel free to use it to build your API.

### Globally expose your React components

In order to make your React components accessible to React Bundle, you need to register them. We are using for this purpose the npm package of the React On Rails, (that can be used outside the Ruby world).

Take a look at the `client/Recipes/startup/serverRegistration.jsx` and `client/Recipes/startup/clientRegistration.jsx` entries:

Server side:

    import ReactOnRails from 'react-on-rails';
    import RecipesApp from './RecipesAppServer';
    
    ReactOnRails.register({ RecipesApp });

Here we import our root component and expose it. The same goes for the client-side part:

    import ReactOnRails from 'react-on-rails';
    import RecipesApp from './RecipesAppClient';
    
    ReactOnRails.register({ RecipesApp });

#### JavaScript code organisation for isomorphic apps
Note that in most cases you will be sharing almost all of your code between your client-side component and its server-side homologous, but while your client-code comes without no surprises, in the server side you will probably have to play a bit with `react-router` in order to make it know the location and set up the routing history. This is a common issue in isomorphic applications. You can find examples on how to do this all along the Internet, but also in the files `client/Recipes/startup/serverRegistration.jsx` and `client/Recipes/startup/clientRegistration.jsx`.


Configuration for Hot-Reloading
===============================

In the development environment is nice to have Webpack with hot-reloading. This means that you run a Webpack server that serves your assets and, if you change something on them, Webpack makes your server reload the page automatically. To run the hot-reloading server run Webpack with:

    webpack --config webpack.config.serverside.js --watch

And also, in `/app/configuration/config_dev.yml`, add these options in the `framework` section:

    framework:
        # ...
        assets:
            packages:
                webpack:
                    base_urls:
                        - "%assets_base_url%"

And, in `paramters.yml` add an `assets_base_url` entry:

    parameters:
        # ...
        assets_base_url: 'http://localhost:8080'

This allows us to use the Webpack server when loading assets in Twig, like:

    <link href="{{asset('assets/build/stylesheets/main.css', 'webpack')}}" rel="stylesheet">

or

    <script src="{{ asset('assets/build/client-bundle.js', 'webpack') }}"></script>

And in dev mode Symfony will load these assets from `http://localhost:8080`.
    


Credits
=======

This project is inspired by the great [React on Rails](https://github.com/shakacode/react_on_rails#), and also uses its JavaScript package.
