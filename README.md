# Xumulus Advanced Bundles GraphQL: Wishlist

Wishlist support for [Xumulus Advanced Bundles GraphQL](https://github.com/xumulus/xumulus-advancebundles-graph-ql) (`xumulus/advancebundles-graph-ql`): the
text a shopper enters for a bundle item, such as an engraving, is kept on a wishlist bundle and moved with it to the
cart.

It is a package of its own so that `xumulus/advancebundles-graph-ql` does not depend on Magento_Wishlist. Install
this one only where Magento_Wishlist and Magento_WishlistGraphQl are enabled.

---

## Requirements

| | |
|---|---|
| Adobe Commerce / Magento Open Source | 2.4.7, 2.4.8, 2.4.9 |
| PHP | 8.1, 8.2, 8.3, 8.4, 8.5 |
| Xumulus Advanced Bundles and its GraphQL package | 2.0.0 or later |
| Magento modules | `Magento_Wishlist`, `Magento_WishlistGraphQl` |

## Installation

```bash
composer require xumulus/advancebundles-graph-ql-wishlist
bin/magento module:enable Xumulus_AdvanceBundlesGraphQlWishlist
bin/magento setup:upgrade
bin/magento setup:di:compile        # production mode only
bin/magento cache:flush
```

There are no settings. It follows the main module's **Enable** setting for each store view: while the extension is
disabled, `xab-custom` entered options are ignored, as in `addProductsToCart`.

## Saving texts on a wishlist item

`addProductsToWishlist` and `updateProductsInWishlist` take the same input as `addProductsToCart`: the bundle items
in `selected_options`, and the shopper's text as `entered_options` with the `uid` from
`BundleItemOption.xab_custom_options`.

```graphql
mutation {
  addProductsToWishlist(
    wishlistId: "4"
    wishlistItems: [{
      sku: "XAB-BUNDLE-FIX"
      quantity: 1
      selected_options: ["YnVuZGxlLzkvMjkvMQ==", "YnVuZGxlLzEwLzM1LzE="]
      entered_options: [{ uid: "eGFiLWN1c3RvbS85LzI5LzE=", value: "For Sam" }]
    }]
  ) {
    user_errors { code message }
  }
}
```

The texts are stored in the wishlist item's request (`bundle_custom_option`), where the storefront keeps them too.

- A malformed uid, the same uid sent twice, or a uid for an item not chosen in the same wishlist item is refused
  with a user error. Core keeps one chosen item per bundle option on a wishlist item (the last one sent), so only
  that item can carry a text.
- Like the storefront, a wishlist item is saved without checking whether required texts are filled in or too long.
  Those checks run when the item is moved to the cart.

## Moving wishlist items to the cart

`addWishlistItemsToCart` carries the texts saved on a wishlist bundle over to the cart, as `xab-custom` entered
options, so they are validated and stored like any other. Texts saved by 1.3.x (in `bundle_option[custom_option]`)
are read too. Empty texts, and texts for items that are not chosen, are left out.

Core's `addWishlistItemsToCart` passes on only options with a single chosen item (select and radio options); it
does not handle checkbox or multi-select options of a wishlist bundle, so their texts cannot be carried either. A
required text that was never saved on the wishlist item fails the move with a user error, as on the storefront.

## Development

CI (`.github/workflows/ci.yml`) runs phpcs and PHPUnit on PHP 8.1 (Magento 2.4.7) and 8.4 (Magento 2.4.9) on
every push. It checks out the private Xumulus packages it builds on into `dev/ci/deps/` with read-only deploy keys,
then runs `dev/ci/run-phpcs.sh` and `dev/ci/run-unit.sh`. With those checkouts in place the scripts run the same
way locally, for example in the CI image:

```bash
docker run --rm -u 0 -e COMPOSER_ALLOW_SUPERUSER=1 -v "$PWD":/app -w /app --entrypoint dev/ci/run-unit.sh \
    markoshust/magento-php:8.4-fpm-2
```

Unit tests, from a Magento install's vendor/ with the main module and the GraphQL package installed (or named by
`XAB_MAIN_MODULE` and `XAB_GRAPHQL_MODULE`, see `dev/phpunit-bootstrap.php`):

```bash
XAB_AUTOLOAD=/var/www/html/vendor/autoload.php /var/www/html/vendor/bin/phpunit -c phpunit.xml.dist
```

The GraphQL package's `Test/Api/run-graphql-tests.sh` covers these mutations when `XAB_CUSTOMER` is set.

Build the release zip from a tag with `git archive --format=zip <tag>`; `.gitattributes` leaves out the
repository-only files.

## License

See [LICENSE.txt](LICENSE.txt).
