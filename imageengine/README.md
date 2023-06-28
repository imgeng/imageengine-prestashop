<h1 align="center"><img src="./logo.png" alt="PrestaShop ImageEngine CDN" width="200"></h1>

# PrestaShop ImageEngine CDN

## About ImageEngine
ImageEngine is a global CDN specializing in optimizing images and static content. 
ImageEngine will optimize the images real time according to the device and browser capabilities producing a lean, 
fast loading and beautiful image with great visual quality. Learn more about ImageEngine

## About PrestaShop ImageEngine CDN module.
This app will help you integrate ImageEngine in your PrestaShop store. Once configuration is saved, 
the app will make sure that your images are optimized and  served from ImageEngine. 
This is done by automatically setting Media server configuration and adding additional requests directives 
in order to optimize connection.
[More details][more-details]

## How to use
1. Download an archive of the latest released version of the [PrestaShop ImageEngine CDN module][direct-download]
2. Connect to the BackOffice of your shop and go to Modules > Module Manager,
then click Upload a module button in the header right and upload the downloaded archive.
3. Lookup ImageEngine module in the modules list and click Configure
4. Set Enable CDN to Enabled, Set CDN URL to the ImageEngine provided URL 
(example: myshop.cdn.imgeng.in, if you don't have it see next step),
Set all of the Add directives to enabled.
5. If you don't yet have an ImageEngine account, there is a button below the URL field that invites you to register.
After registering you will obtain the ImageEngine CDN url that you can set in the CDN URL field, 
in the form of "yourshop.cdn.imgeng.in".
If you already have a URL set, the button will link to your ImageEngine account dashboard.
6. Save configuration

All image and css/js assets should now be served by ImageEngine CDN. 
You can check this by inspecting the page source of any of your shop frontend pages.

## How it works
The module works by setting the CDN URL to Media server #1 provided in the PrestaShop Performance CCC settings.
CSS and JavaScript files will also be cached and served via the CDN.

If the Media server fields are already configured before enabling the ImageEngine plugin and saving configuration, 
a warning will be shown, informing that the current Media server values will be replaced.

If the Media server fields are changed by the user or by other plugin, after enabling ImageEngine configuration, 
a warning will be shown indicating inconsistent configuration was detected, and invites to resave ImageEngine 
configuration to restore proper settings required for ImageEngine CDN.

### Direct download

If you want to get a zip ready to install on your shop, you can directly download it by clicking [here][direct-download].

[direct-download]: https://github.com/imgeng/imageengine-prestashop/releases/latest/download/imageengine.zip
[more-details]: https://support.imageengine.io/hc/en-us/articles/360059820731-PrestaShop-CDN