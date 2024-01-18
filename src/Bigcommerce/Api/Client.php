<?php
namespace Bigcommerce\Api;

use Firebase\JWT\JWT;
use \Exception;
use \stdClass;
use \DateTime;

/**
 * Bigcommerce API Client.
 */
class Client
{
    /** @var string Full Store URL to connect to */
	static private string $store_url;

    /** @var string|null Username to connect to the store API with */
	static private string|null $username;

    /** @var string|null API key */
	static private string|null $api_key;

    /** @var Connection|false Connection instance */
    static private Connection|false $connection;

    /** @var string Resource class name */
	static private string $resource;

    /** @var string API path prefix to be added to store URL for requests */
	static private string $path_prefix = '/api/v2';

    /** @var string|null The OAuth client ID */
    static private string|null $client_id;

    /** @var string The OAuth client secret */
	static private string $client_secret;

    /** @var string|null The OAuth Auth-Token */
	static private string|null $auth_token;

    /** @var string The store hash */
	static private string $store_hash;

    /** @var string URL pathname prefix for the V2 API */
	static private string $stores_prefix = '/stores/%s/%s';

    /** @var string The BigCommerce store management API host */
	static private string $api_url = 'https://api.bigcommerce.com';

    /** @var string The BigCommerce merchant login URL */
	static private string $login_url = 'https://login.bigcommerce.com';

    /** @var string API version */
	static private string $version = 'v2';

    /** @var string SSL cipher used for the connection */
	static private string $cipher;

    /** @var bool true if the current connection requires SSL verification */
	static private bool $verifyPeer;

	/**
	 * Full URL path to the configured store API.
	 *
	 * @var string
	 */
	static public string $api_path;

	/**
	 * Configure the API client with the required credentials.
	 *
	 * Requires a settings array to be passed in with the following keys:
	 *
	 * - store_url
	 * - username
	 * - api_key
	 *
	 * @param array $settings
	 * @throws Exception
	 */
	public static function configureBasicAuth(array $settings)
	{
		if (!isset($settings['store_url'])) {
			throw new Exception("'store_url' must be provided");
		}

		if (!isset($settings['username'])) {
			throw new Exception("'username' must be provided");
		}

		if (!isset($settings['api_key'])) {
			throw new Exception("'api_key' must be provided");
		}

		self::$client_id = null;
		self::$auth_token = null;
		self::$username  = $settings['username'];
		self::$api_key 	 = $settings['api_key'];
		self::$store_url = rtrim($settings['store_url'], '/');
		self::$api_path  = self::$store_url . self::$path_prefix;
		self::$connection = false;
	}

	/**
	 * Configure the API client with the required OAuth credentials.
	 *
	 * Requires a settings array to be passed in with the following keys:
	 *
	 * - client_id
	 * - auth_token
	 * - store_hash
	 *
	 * @param array $settings
	 * @throws Exception
	 */
	public static function configureOAuth(array $settings)
	{
		if (!isset($settings['auth_token'])) {
			throw new Exception("'auth_token' must be provided");
		}

		if (!isset($settings['store_hash'])) {
			throw new Exception("'store_hash' must be provided");
		}

		self::$username  = null;
		self::$api_key 	 = null;
		self::$client_id = $settings['client_id'];
		self::$auth_token = $settings['auth_token'];
		self::$store_hash = $settings['store_hash'];

		self::$client_secret = $settings['client_secret'] ?? null;

		self::$api_path = self::$api_url . sprintf(self::$stores_prefix, self::$store_hash, self::$version);
		self::$connection = false;
	}

	/**
	 * Configure the API client with the required settings to access
	 * the API for a store.
	 *
	 * Accepts both OAuth and Basic Auth credentials
	 *
	 * @param array $settings
	 * @throws Exception
	 */
	public static function configure(array $settings)
	{
		if (isset($settings['client_id'])) {
			self::configureOAuth($settings);
		} else {
			self::configureBasicAuth($settings);
		}
	}

	/**
	 * Configure the API client with the Bigcommerce API version
	 *
	 * @param string $version
	 */
	public static function setVersion(string $version)
	{
		self::$version = $version;

		if (!empty(self::$client_id)) {
			self::$api_path = self::$api_url . sprintf(self::$stores_prefix, self::$store_hash, self::$version);
		}
	}

	/**
	 * Configure the API client to throw exceptions when HTTP errors occur.
	 *
	 * Note that network faults will always cause an exception to be thrown.
	 *
	 * @param bool $option
	 */
	public static function failOnError(bool $option = true)
	{
		self::connection()->failOnError($option);
	}

	/**
	 * Configure the API client to auto retry requests when it fails
	 * due to too many requests.
	 *
	 * @param bool $retry
	 */
	public static function autoRetry(bool $retry = true)
	{
		self::connection()->setAutoRetry($retry);
	}

	/**
	 * Return XML strings from the API instead of building objects.
	 */
	public static function useXml()
	{
		self::connection()->useXml();
	}

	/**
	 * Return JSON objects from the API instead of XML Strings.
	 * This is the default behavior.
	 */
	public static function useJson()
	{
		self::connection()->useXml(false);
	}

	/**
	 * Switch SSL certificate verification on requests.
	 *
	 * @param bool $option
	 */
	public static function verifyPeer(bool $option = false)
	{
		self::$verifyPeer = $option;
		self::connection()->verifyPeer($option);
	}

	/**
	 * Set which cipher to use during SSL requests.
	 *
	 * @param string $cipher
	 */
	public static function setCipher(string $cipher = 'TLSv1')
	{
		self::$cipher = $cipher;
		self::connection()->setCipher($cipher);
	}

	/**
	 * Connect to the internet through a proxy server.
	 *
	 * @param string $host host server
	 * @param int|bool $port port
	 */
	public static function useProxy(string $host, bool|int $port = false)
	{
		self::connection()->useProxy($host, $port);
	}

	/**
	 * Get error message returned from the last API request if
	 * failOnError is false (default).
	 *
	 * @return string
	 */
	public static function getLastError() : string
	{
		return self::connection()->getLastError();
	}

	/**
	 * Get an instance of the HTTP connection object. Initializes
	 * the connection if it is not already active.
	 *
	 * @return Connection
	 */
	private static function connection() : Connection
	{
		if (!self::$connection) {
			self::$connection = new Connection();

			if (self::$client_id) {
				self::$connection->authenticateOauth(self::$client_id, self::$auth_token);
			} else {
				self::$connection->authenticateBasic(self::$username, self::$api_key);
			}
		}

		return self::$connection;
	}

	/**
	 * Convenience method to return instance of the connection
	 *
	 * @return Connection
	 */
	public static function getConnection() : Connection
	{
		return self::connection();
	}
	/**
	 * Set the HTTP connection object. DANGER: This can screw up your Client!
	 *
	 * @param Connection|null $connection The connection to use
	 */
	public static function setConnection(Connection $connection = null)
	{
		self::$connection = $connection;
	}

	/**
	 * Get a collection result from the specified endpoint.
	 *
	 * @param string $path api endpoint
	 * @param string $resource resource class to map individual items
	 * @return array|string mapped collection or XML string if useXml is true
	 *
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCollection(string $path, string $resource = 'Resource') : array|string
	{
		$response = self::connection()->get(self::$api_path . $path);

		return self::mapCollection($resource, $response);
	}

	/**
	 * Get a resource entity from the specified endpoint.
	 *
	 * @param string $path api endpoint
	 * @param string $resource resource class to map individual items
	 * @return string|Resource Resource|string resource object or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getResource(string $path, string $resource = 'Resource') : string|Resource
	{
		$response = self::connection()->get(self::$api_path . $path);

		return self::mapResource($resource, $response);
	}

	/**
	 * Get a count value from the specified endpoint.
	 *
	 * @param string $path api endpoint
	 * @return int|string count value or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCount(string $path) : int|string
	{
		$response = self::connection()->get(self::$api_path . $path);

		if ($response === false || is_string($response)) return $response;

		return (int)$response->count;
	}

	/**
	 * Send a post request to create a resource on the specified collection.
	 *
	 * @param string $path api endpoint
	 * @param mixed $object object or XML string to create
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createResource(string $path, mixed $object) : mixed
	{
		if (is_array($object)) $object = (object)$object;

		return self::connection()->post(self::$api_path . $path, $object);
	}

	/**
	 * Send a put request to update the specified resource.
	 *
	 * @param string $path api endpoint
	 * @param mixed $object object or XML string to update
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public static function updateResource(string $path, mixed $object) : mixed
	{
		if (is_array($object)) $object = (object)$object;

		return self::connection()->put(self::$api_path . $path, $object);
	}

	/**
	 * Send a delete request to remove the specified resource.
	 *
	 * @param string $path api endpoint
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public static function deleteResource(string $path) : mixed
	{
		return self::connection()->delete(self::$api_path . $path);
	}

	/**
	 * Internal method to wrap items in a collection to resource classes.
	 *
	 * @param string $resource name of the resource class
	 * @param mixed $object object collection
	 * @return array|string|bool
	 */
	private static function mapCollection(string $resource, mixed $object) : array|string|bool
	{
		if ($object == false || is_string($object)) return $object;

		if (!is_array($object)) {
			$object = [ $object ];
		}

		$baseResource = __NAMESPACE__ . '\\' . $resource;
		self::$resource = (class_exists($baseResource)) ?  $baseResource  :  'Bigcommerce\\Api\\Resources\\' . $resource;

		return array_map(['self', 'mapCollectionObject'], $object);
	}

    /**
     * Callback for mapping collection objects resource classes.
     *
     * @param stdClass[]|stdClass $object
     * @return Resource
     */
	private static function mapCollectionObject(array|stdClass $object) : Resource
	{
		$class = self::$resource;

		return new $class($object);
	}

	/**
	 * Map a single object to a resource class.
	 *
	 * @param string $resource name of the resource class
	 * @param mixed $object
	 * @return Resource|string|bool
	 */
	private static function mapResource(string $resource, mixed $object) : Resource|string|bool
	{
		if ($object == false || is_string($object)) return $object;

		$baseResource = __NAMESPACE__ . '\\' . $resource;
		$class = (class_exists($baseResource)) ? $baseResource : 'Bigcommerce\\Api\\Resources\\' . $resource;

		return new $class($object);
	}

	/**
	 * Swaps a temporary access code for a long expiry auth token.
	 *
	 * @param mixed $object
	 * @return stdClass
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public static function getAuthToken(mixed $object) : mixed
	{
		$context = array_merge(array(
			'grant_type' => 'authorization_code'
		), (array)$object);

		$connection = new Connection();
		$connection->useUrlencoded();

		// update with previously selected option
		if (self::$cipher) $connection->setCipher(self::$cipher);
		if (self::$verifyPeer) $connection->verifyPeer(self::$verifyPeer);

		return $connection->post(self::$login_url . '/oauth2/token', $context);
	}

	/**
	 * generate login token
	 *
	 * @param int $id
	 * @param string $redirectUrl
	 * @param string $requestIp
	 * @return string
	 * @throws Exception
	 */
	public static function getCustomerLoginToken(int $id, string $redirectUrl = '', string $requestIp = '') : string
	{
		if (empty(self::$client_secret)) {
			throw new Exception('Cannot sign customer login tokens without a client secret');
		}

		$payload = array(
			'iss' => self::$client_id,
			'iat' => time(),
			'jti' => bin2hex(random_bytes(32)),
			'operation' => 'customer_login',
			'store_hash' => self::$store_hash,
			'customer_id' => $id
		);

		if (!empty($redirectUrl)) {
			$payload['redirect_to'] = $redirectUrl;
		}

		if (!empty($requestIp)) {
			$payload['request_ip'] = $requestIp;
		}

		return JWT::encode($payload, self::$client_secret, 'HS256');
	}

	/**
	 * Pings the time endpoint to test the connection to a store.
	 *
	 * @return DateTime|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getTime() : DateTime|string
	{
		$response = self::connection()->get(self::$api_path . '/time');

		if ($response == false || is_string($response)) return $response;

		return new DateTime("@$response->time");
	}

	/**
	 * Returns the default collection of products.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProducts(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);

		return self::getCollection('/products' . $filter->toQuery(), 'Product');
	}

	/**
	 * Returns the total number of products in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string number of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductsCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/products/count' . $filter->toQuery());
	}

	/**
	 * Returns the collection of configurable fields
	 *
	 * @param int $id product id
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductConfigurableFields(int $id, array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/'.$id.'/configurablefields' . $filter->toQuery(), "ProductConfigurableField");
	}

	/**
	 * The total number of configurable fields in the collection.
	 *
	 * @param int $id product id
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductConfigurableFieldsCount(int $id, array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/products/'.$id.'/configurablefields/count' . $filter->toQuery());
	}

	/**
	 * Returns the collection of discount rules
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductDiscountRules(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/discount_rules' . $filter->toQuery());
	}

	/**
	 * The total number of discount rules in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductDiscountRulesCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);

		return self::getCount('/products/discount_rules/count' . $filter->toQuery());
	}

	/**
	 * Create a new product image.
	 *
	 * @param int $productId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createProductImage(int $productId, mixed $object) : mixed
	{
		return self::createResource('/products/' . $productId . '/images', $object);
	}

	/**
	 * Update a product image.
	 *
	 * @param int $productId
	 * @param int $imageId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateProductImage(int $productId, int $imageId, mixed $object) : mixed
	{
		return self::updateResource('/products/' . $productId . '/images/' . $imageId, $object);
	}

	/**
	 * Returns a product image resource by the given product id.
	 *
	 * @param int $productId
	 * @param int $imageId
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductImage(int $productId, int $imageId) : Resource|string
	{
		return self::getResource('/products/' . $productId . '/images/' . $imageId, 'ProductImage');
	}

	/**
	 * Gets collection of images for a product.
	 *
	 * @param int $id product id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductImages(int $id) : array|string
	{
		return self::getCollection('/products/' . $id . '/images', 'ProductImage');
	}

	/**
	 * Delete the given product image.
	 *
	 * @param int $productId
	 * @param int $imageId
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteProductImage(int $productId, int $imageId) : mixed
	{
		return self::deleteResource('/products/' . $productId . '/images/' . $imageId);
	}

	/**
	 * Returns the collection of product images
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductsImages(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/images' . $filter->toQuery(), "ProductImage");
	}

	/**
	 * The total number of product images in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductsImagesCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/products/images/count' . $filter->toQuery());
	}

	/**
	 * Return the collection of all option values for a given option.
	 *
	 * @param int $productId
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductOptions(int $productId) : array|string
	{
		return self::getCollection('/products/' . $productId . '/options');
	}

	/**
	 * Return the collection of all option values for a given option.
	 *
	 * @param int $productId
	 * @param int $productOptionId
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductOption(int $productId, int $productOptionId) : Resource|string
	{
		return self::getResource('/products/' . $productId . '/options/' . $productOptionId);
	}

	/**
	 * Return the collection of all option values for a given option.
	 *
	 * @param int $productId
	 * @param int $productRuleId
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductRule(int $productId, int $productRuleId) : Resource|string
	{
		return self::getResource('/products/' . $productId . '/rules/' . $productRuleId);
	}

	/**
	 * Returns the collection of product rules
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductRules(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/rules'  . $filter->toQuery(), "Rule");
	}

	/**
	 * The total number of product rules in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductRulesCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/products/rules/count' . $filter->toQuery());
	}

	/**
	 * Returns the collection of product skus
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductSkus(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/skus' . $filter->toQuery(), "Sku");
	}

	/**
	 * The total number of product skus in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductSkusCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/products/skus/count' . $filter->toQuery());
	}

	/**
	 * Returns the collection of product videos
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public static function getProductVideos(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/videos' . $filter->toQuery(), "ProductVideo");
	}

	/**
	 * The total number of product videos in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public static function getProductVideosCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/products/videos/count' . $filter->toQuery());
	}

	/**
	 * Gets collection of custom fields for a product.
	 *
	 * @param int $id product ID
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public static function getProductCustomFields(int $id): array|string
	{
		return self::getCollection('/products/' . $id . '/customfields/', 'ProductCustomField');
	}

	/**
	 * Returns a single custom field by given id
	 * @param  int $productId   product id
	 * @param  int $id         custom field id
	 * @return Resource|string Returns ProductCustomField if exists, false if not exists
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductCustomField(int $productId, int $id) : Resource|string
	{
		return self::getResource('/products/' . $productId . '/customfields/' . $id, 'ProductCustomField');
	}

	/**
	 * Create a new custom field for a given product.
	 *
	 * @param int $productId product id
	 * @param mixed $object fields to create
	 * @return mixed Object with `id`, `product_id`, `name` and `text` keys
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createProductCustomField(int $productId, mixed $object) : mixed
	{
		return self::createResource('/products/' . $productId . '/customfields', $object);
	}

	/**
	 * Update the given custom field.
	 *
	 * @param int $productId product id
	 * @param int $id custom field id
	 * @param mixed $object custom field to update
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateProductCustomField(int $productId, int $id, mixed $object) : mixed
	{
		return self::updateResource('/products/' . $productId . '/customfields/' . $id, $object);
	}

	/**
	 * Delete the given custom field.
	 *
	 * @param int $productId product id
	 * @param int $id custom field id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteProductCustomField(int $productId, int $id) : mixed
	{
		return self::deleteResource('/products/' . $productId . '/customfields/' . $id);
	}

	/**
	 * Returns the collection of custom fields
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductsCustomFields(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/customfields' . $filter->toQuery(), "ProductCustomField");
	}

	/**
	 * The total number of custom fields in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductsCustomFieldsCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/products/customfields/count' . $filter->toQuery());
	}

	/**
	 * Gets collection of reviews for a product.
	 *
	 * @param int $id
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProductReviews(int $id) : array|string
	{
		return self::getCollection('/products/' . $id . '/reviews/', 'ProductReview');
	}

	/**
	 * Returns a single product resource by the given id.
	 *
	 * @param int $id product id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getProduct(int $id) : Resource|string
	{
		return self::getResource('/products/' . $id, 'Product');
	}

	/**
	 * Create a new product.
	 *
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createProduct(mixed $object) : mixed
	{
		return self::createResource('/products', $object);
	}

	/**
	 * Update the given product.
	 *
	 * @param int $id product id
	 * @param mixed $object fields to update
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateProduct(int $id, mixed $object) : mixed
	{
		return self::updateResource('/products/' . $id, $object);
	}

	/**
	 * Delete the given product.
	 *
	 * @param int $id product id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteProduct(int $id) : mixed
	{
		return self::deleteResource('/products/' . $id);
	}

	/**
	 * Return the collection of options.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptions(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/options' . $filter->toQuery(), 'Option');
	}

	/**
	 * create options
	 *
	 * @param stdClass $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createOptions(mixed $object) : mixed
	{
		return self::createResource('/options', $object);
	}


	/**
	 * Return the number of options in the collection
	 *
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionsCount() : int|string
	{
		return self::getCount('/options/count');
	}

	/**
	 * Return a single option by given id.
	 *
	 * @param int $id option id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOption(int $id) : Resource|string
	{
		return self::getResource('/options/' . $id, 'Option');
	}



	/**
	 * Delete the given option.
	 *
	 * @param int $id option id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteOption(int $id) : mixed
	{
		return self::deleteResource('/options/' . $id);
	}

	/**
	 * Return a single value for an option.
	 *
	 * @param int $optionId option id
	 * @param int $valueId value id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionValue(int $optionId, int $valueId) : Resource|string
	{
		return self::getResource('/options/' . $optionId . '/values/' . $valueId, 'OptionValue');
	}

	/**
	 * Return the collection of all option values.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionValues(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/options/values' . $filter->toQuery(), 'OptionValue');
	}

	/**
	 * The number of option values in the collection.
	 *
	 * @return int
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionValuesCount() : int
	{
		$page = 1;
		$filter = Filter::create([ 'page' => $page, 'limit' => 250 ]);

		$data = self::getOptionValues($filter);
		$count = count($data);
        
		while ($data) {
			$page++;
			$filter = Filter::create([ 'page' => $page, 'limit' => 250 ]);
			$data = self::getOptionValues($filter);
			$count += count($data);
		}

		return $count;
	}

	/**
	 * The collection of categories.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCategories(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/categories' . $filter->toQuery(), 'Category');
	}

	/**
	 * The number of categories in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCategoriesCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/categories/count' . $filter->toQuery());
	}

	/**
	 * A single category by given id.
	 *
	 * @param int $id category id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCategory(int $id) : Resource|string
	{
		return self::getResource('/categories/' . $id, 'Category');
	}

	/**
	 * Create a new category from the given data.
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCategory(mixed $object) : mixed
	{
		return self::createResource('/categories/', $object);
	}

	/**
	 * Update the given category.
	 *
	 * @param int $id category id
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCategory(int $id, mixed $object) : mixed
	{
		return self::updateResource('/categories/' . $id, $object);
	}

	/**
	 * Delete the given category.
	 *
	 * @param int $id category id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCategory(int $id) : mixed
	{
		return self::deleteResource('/categories/' . $id);
	}

	/**
	 * The collection of brands.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getBrands(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/brands' . $filter->toQuery(), 'Brand');
	}

	/**
	 * The total number of brands in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getBrandsCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/brands/count' . $filter->toQuery());
	}

	/**
	 * A single brand by given id.
	 *
	 * @param int $id brand id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getBrand(int $id) : Resource|string
	{
		return self::getResource('/brands/' . $id, 'Brand');
	}

	/**
	 * Create a new brand from the given data.
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createBrand(mixed $object) : mixed
	{
		return self::createResource('/brands', $object);
	}

	/**
	 * Update the given brand.
	 *
	 * @param int $id brand id
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateBrand(int $id, mixed $object) : mixed
	{
		return self::updateResource('/brands/' . $id, $object);
	}

	/**
	 * Delete the given brand.
	 *
	 * @param int $id brand id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteBrand(int $id) : mixed
	{
		return self::deleteResource('/brands/' . $id);
	}

	/**
	 * The collection of orders.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrders(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/orders' . $filter->toQuery(), 'Order');
	}

	/**
	 * The number of orders in the collection.
	 *
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrdersCount() : int|string
	{
		return self::getCount('/orders/count');
	}

	/**
	 * A single order.
	 *
	 * @param int $id order id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrder(int $id) : Resource|string
	{
		return self::getResource('/orders/' . $id, 'Order');
	}

	/**
	 * Delete the given order (unlike in the Control Panel, this will permanently
	 * delete the order).
	 *
	 * @param int $id order id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteOrder(int $id) : mixed
	{
		return self::deleteResource('/orders/' . $id);
	}

	/**
	 * Create an order
	 *
	 * @param stdClass $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 **/
	public static function createOrder(mixed $object) : mixed
	{
		return self::createResource('/orders', $object);
	}

	/**
	 * Returns the collection of order coupons
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderCoupons(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/orders/coupons' . $filter->toQuery(), 'Coupon');
	}

	/**
	 * The total number of order coupons in the collection.
	 *
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderCouponsCount() : int|string
	{
		$page = 1;
		$filter = Filter::create([ 'page' => $page, 'limit' => 250 ]);
		$data = self::getOrderCoupons($filter);
		$count = count($data);

		while ($data) {
			$page++;
			$filter = Filter::create([ 'page' => $page, 'limit' => 250 ]);
			$data = self::getOrderCoupons($filter);
			$count += count($data);
		}

		return $count;
	}

	/**
	 * Returns the collection of order products
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderProducts(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/orders/products' . $filter->toQuery(), 'Product');
	}

	/**
	 * The total number of order products in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderProductsCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/orders/products/count' . $filter->toQuery());
	}

	/**
	 * Returns the collection of order shipments
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderShipments(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/orders/shipments' . $filter->toQuery());
	}

	/**
	 * The total number of order shipments in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderShipmentsCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/orders/shipments/count' . $filter->toQuery());
	}

	/**
	 * Returns the collection of shipping addresses
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderShippingAddresses(array|int|bool|Filter $filter = false) : array|string
    {
		$filter = Filter::create($filter);
		return self::getCollection('/orders/shippingaddresses/' . $filter->toQuery(), "Address");
	}

	/**
	 * The total number of shipping addresses in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderShippingAddressesCount(array|int|bool|Filter $filter = false) : int|string
    {
		$filter = Filter::create($filter);
		return self::getCount('/orders/shippingaddresses/count' . $filter->toQuery());
	}

	/**
	 * The list of customers.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomers(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/customers' . $filter->toQuery(), 'Customer');
	}

	/**
	 * The total number of customers in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomersCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/customers/count' . $filter->toQuery());
	}

	/**
	 * The total number of customers in the collection.
	 *
	 * @param array|int|bool|Filter $filter
     * @param bool $isDataOnly
	 * @return Resource|array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomerAttributes(array|int|bool|Filter $filter = false, bool $isDataOnly = false) : Resource|array|string
	{
		$filter = Filter::create($filter);
		$response = self::getV3Resource('/customers/attributes' . $filter->toQuery());

		if ($isDataOnly) {
			if (!empty($response->data)) {
				return self::mapCollection('Resource', $response->data);
			}
		}

		return $response;
	}

	/**
	 * The total number of customers in the collection.
	 *
	 * @param array|int|bool|Filter $filter
     * @param bool $isDataOnly
	 * @return Resource|array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomerAttributeValues(array|int|bool|Filter $filter = false, bool $isDataOnly = false) : Resource|array|string
	{
		$filter = Filter::create($filter);
		$response = self::getV3Resource('/customers/attribute-values' . $filter->toQuery());

        if ($isDataOnly) {
			if (!empty($response->data)) {
				return self::mapCollection('Resource', $response->data);
			}
		}

		return $response;
	}

	/**
	 * The total number of customer Attributes.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomerAttributesCount(array|int|bool|Filter $filter = false) : int
	{
		$ret = self::getCustomerAttributes($filter);

		if (!empty($ret->meta) && !empty($ret->meta->pagination) && !empty($ret->meta->pagination->total_pages)) {
			return (int)$ret->meta->pagination->total_pages;
		}
		
		return 0;
	}

	/**
	 * The total number of customers in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomerAttributeValuesCount(array|int|bool|Filter $filter = false) : int
	{
		$ret = self::getCustomerAttributeValues($filter);

		if (!empty($ret->meta) && !empty($ret->meta->pagination) && !empty($ret->meta->pagination->total_pages)) {
			return (int)$ret->meta->pagination->total_pages;
		}

		return 0;
	}

	/**
	 * Bulk delete customers.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCustomers(array|int|bool|Filter $filter = false) : mixed
	{
		$filter = Filter::create($filter);
		return self::deleteResource('/customers' . $filter->toQuery());
	}

	/**
	 * A single customer by given id.
	 *
	 * @param int $id customer id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomer(int $id) : Resource|string
	{
		return self::getResource('/customers/' . $id, 'Customer');
	}

	/**
	 * Create a new customer from the given data.
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCustomer(mixed $object) : mixed
	{
		return self::createResource('/customers', $object);
	}

	/**
	 * Update the given customer.
	 *
	 * @param int $id customer id
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCustomer(int $id, mixed $object) : mixed
	{
		return self::updateResource('/customers/' . $id, $object);
	}

	/**
	 * Delete the given customer.
	 *
	 * @param int $id customer id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCustomer(int $id) : mixed
	{
		return self::deleteResource('/customers/' . $id);
	}

	/**
	 * A list of addresses belonging to the given customer.
	 *
	 * @param int $id customer id
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomerAddresses(int $id) : array|string
	{
		return self::getCollection('/customers/' . $id . '/addresses', 'Address');
	}

	/**
	 * Returns the collection of customer addresses
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomersAddresses(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/customers/addresses' . $filter->toQuery(), "Address");
	}

	/**
	 * The number of customer addresses in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomersAddressesCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/customers/addresses/count' . $filter->toQuery());
	}

	/**
	 * The number of customer groups in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomerGroupsCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/customer_groups/count' . $filter->toQuery());
	}

	/**
	 * Returns the collection of option sets.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionSets(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/optionsets' . $filter->toQuery(), 'OptionSet');
	}

	/**
     * create optionsets
     *
     * @param mixed $object
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public static function createOptionsets(mixed $object) : mixed
	{
		return self::createResource('/optionsets', $object);
	}

	/**
     * connect optionsets options
     *
     * @param mixed $object
     * @param int $id
     * @return mixed
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
	public static function createOptionsetsOptions(mixed $object, int $id) : mixed
	{
		return self::createResource("/optionsets/$id/options", $object);
	}


	/**
	 * Returns the total number of option sets in the collection.
	 *
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionSetsCount() : int|string
	{
		return self::getCount('/optionsets/count');
	}

	/**
	 * A single option set by given id.
	 *
	 * @param int $id option set id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionSet(int $id) : Resource|string
	{
		return self::getResource('/optionsets/' . $id, 'OptionSet');
	}

	/**
	 * Returns the collection of optionset options
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionSetOptions(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/optionsets/options' . $filter->toQuery(), 'Option');
	}

	/**
	 * The number of optionset options in the collection.
	 *
	 * @return int
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOptionSetOptionsCount() : int
	{
		$page = 1;
		$filter = Filter::create([ 'page' => $page, 'limit' => 250 ]);
		$data = self::getOptionSetOptions($filter);
		$count = count($data);

		while ($data) {
			$page++;
			$filter = Filter::create([ 'page' => $page, 'limit' => 250 ]);
			$data = self::getOptionSetOptions($filter);
			$count += count($data);
		}

		return $count;
	}

	/**
	 * Status codes used to represent the state of an order.
	 *
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getOrderStatuses() : array|string
	{
		return self::getCollection('/orderstatuses', 'OrderStatus');
	}

    /**
     * Return a collection of shipping-zones
     *
     * @return array|string
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
    public static function getShippingZones() : array|string
    {
        return self::getCollection('/shipping/zones', 'ShippingZone');
    }

    /**
     * Return a shipping-zone by id
     *
     * @param int $id shipping-zone id
     * @return Resource|string
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
    public static function getShippingZone(int $id) : Resource|string
    {
        return self::getResource('/shipping/zones/' . $id, 'ShippingZone');
    }


    /**
     * Delete the given shipping-zone
     *
     * @param int $id shipping-zone id
     * @return mixed
     * @return array|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
    public static function deleteShippingZone(int $id) : mixed
    {
        return self::deleteResource('/shipping/zones/' . $id);
    }

	/**
	 * Enabled shipping methods.
	 *
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getShippingMethods() : array|string
	{
		return self::getCollection('/shipping/methods', 'ShippingMethod');
	}

	/**
	 * Get collection of skus for all products
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getSkus(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/products/skus' . $filter->toQuery(), 'Sku');
	}

	/**
	 * Create a SKU
	 *
	 * @param int $productId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createSku(int $productId, mixed $object) : mixed
	{
		return self::createResource('/products/' . $productId . '/skus', $object);
	}

	/**
	 * Update sku
	 *
	 * @param int $id
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateSku(int $id, mixed $object) : mixed
	{
		return self::updateResource('/product/skus/' . $id, $object);
	}

	/**
	 * Returns the total number of SKUs in the collection.
	 *
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getSkusCount() : int|string
	{
		return self::getCount('/products/skus/count');
	}

    /**
     * Returns the googleproductsearch mapping for a product.
     *
     * @return Resource|string
     * @throws ClientError
     * @throws NetworkError
     * @throws ServerError
     */
    public static function getGoogleProductSearch($productId) : Resource|string
    {
        return self::getResource('/products/' . $productId . '/googleproductsearch', 'ProductGoogleProductSearch');
    }

	/**
	 * Get a single coupon by given id.
	 *
	 * @param int $id customer id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCoupon(int $id) : Resource|string
	{
		return self::getResource('/coupons/' . $id, 'Coupon');
	}

	/**
	 * Get coupons
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCoupons(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/coupons' . $filter->toQuery(), 'Coupon');
	}

	/**
	 * Create coupon
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCoupon(mixed $object) : mixed
	{
		return self::createResource('/coupons', $object);
	}

	/**
	 * Update coupon
	 *
	 * @param int $id
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCoupon(int $id, mixed $object) : mixed
	{
		return self::updateResource('/coupons/' . $id, $object);
	}

	/**
	 * Delete the given coupon.
	 *
	 * @param int $id coupon id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCoupon(int $id) : mixed
	{
		return self::deleteResource('/coupons/' . $id);
	}

	/**
	 * Delete all Coupons.
	 *
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteAllCoupons() : mixed
	{
		return self::deleteResource('/coupons');
	}

	/**
	 * Return the number of coupons
	 *
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCouponsCount() : int|string
	{
		return self::getCount('/coupons/count');
	}

	/**
	 * Returns the collection of redirects
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getRedirects(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/redirects' . $filter->toQuery(), 'OptionSet');
	}

	/**
	 * The total number of redirects in the collection.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return int|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getRedirectsCount(array|int|bool|Filter $filter = false) : int|string
	{
		$filter = Filter::create($filter);
		return self::getCount('/redirects/count' . $filter->toQuery());
	}

	/**
	 * Returns store details.
	 *
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getStore() : mixed
	{
        return self::connection()->get(self::$api_path . '/store');
	}

	/**
	 * The number of requests remaining at the current time. Based on the
	 * last request that was fetched within the current script. If no
	 * requests have been made, pings the time endpoint to get the value.
	 *
	 * @return int
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getRequestsRemaining() : int
	{
		$limit = self::connection()->getHeader('x-bc-apilimit-remaining');

		if (!$limit) {
			$result = self::getTime();

			if (!$result) return false;

			$limit = self::connection()->getHeader('x-bc-apilimit-remaining');
		}

		return intval($limit);
	}

	/**
	 * Get a single shipment by given id.
	 *
	 * @param int $orderId
	 * @param int $shipmentId
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getShipment(int $orderId, int $shipmentId) : Resource|string
	{
		return self::getResource('/orders/' . $orderId . '/shipments/' . $shipmentId, 'Shipment');
	}

	/**
	 * Get shipments for a given order
	 *
	 * @param int $orderId
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getShipments(int $orderId, array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection("/orders/$orderId/shipments" . $filter->toQuery(), 'Shipment');
	}

	/**
	 * Create shipment
	 *
	 * @param int $orderId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createShipment(int $orderId, mixed $object) : mixed
	{
		return self::createResource("/orders/$orderId/shipments", $object);
	}

	/**
	 * Update shipment
	 *
	 * @param int $orderId
	 * @param int $shipmentId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateShipment(int $orderId, int $shipmentId, mixed $object) : mixed
	{
		return self::updateResource("/orders/$orderId/shipments/" . $shipmentId, $object);
	}

	/**
	 * Delete the given shipment.
	 *
	 * @param int $orderId
	 * @param int $shipmentId
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteShipment(int $orderId, int $shipmentId) : mixed
	{
		return self::deleteResource("/orders/$orderId/shipments/" . $shipmentId);
	}

	/**
	 * Delete all Shipments for the given order.
	 *
	 * @param int $orderId
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteAllShipmentsForOrder(int $orderId) : mixed
	{
		return self::deleteResource("/orders/$orderId/shipments");
	}

	/**
	 * Create a new currency.
	 *
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCurrency(mixed $object) : mixed
	{
		return self::createResource('/currencies', $object);
	}

	/**
	 * Returns a single currency resource by the given id.
	 *
	 * @param int $id currency id
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCurrency(int $id) : Resource|string
	{
		return self::getResource('/currencies/' . $id, 'Currency');
	}

	/**
	 * Update the given currency.
	 *
	 * @param int $id currency id
	 * @param mixed $object fields to update
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCurrency(int $id, mixed $object) : mixed
	{
		return self::updateResource('/currencies/' . $id, $object);
	}

	/**
	 * Delete the given currency.
	 *
	 * @param int $id currency id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCurrency(int $id) : mixed
	{
		return self::deleteResource('/currencies/' . $id);
	}

	/**
	 * Returns the default collection of currencies.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string list of currencies or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCurrencies(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/currencies' . $filter->toQuery(), 'Currency');
	}

	/**
	 * get list of webhooks
	 *
	 * @return 	array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getWebhooks() : array|string
	{
		return self::getCollection('/hooks', 'Webhook');
	}

	/**
	 * get a specific webhook by id
	 *
	 * @params int $id webhook id
	 * @return Resource|string $object
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getWebhook(int $id) : Resource|string
	{
		return self::getResource('/hooks/' . $id, 'Webhook');
	}

	/**
	 * create webhook
	 * @param stdClass $object webhook params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createWebhook(mixed $object) : mixed
	{
		return self::createResource('/hooks', $object);
	}

	/**
	 * create a webhook
	 * @param int $id webhook id
	 * @param mixed $object webhook params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateWebhook(int $id, mixed $object) : mixed
	{
		return self::updateResource('/hooks/' . $id, $object);
	}

	/**
	 * delete a webhook
	 * @param int $id webhook id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteWebhook(int $id) : mixed
	{
		return self::deleteResource('/hooks/' . $id);
	}

	/**
	 * Get all content pages
	 *
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getPages() : array|string
	{
		return self::getCollection('/pages', 'Page');
	}

	/**
	 * Get single content pages
	 *
	 * @param int $pageId
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getPage(int $pageId) : Resource|string
	{
		return self::getResource('/pages/' . $pageId, 'Page');
	}

	/**
	 * Create a new content pages
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createPage(mixed $object) : mixed
	{
		return self::createResource('/pages', $object);
	}

	/**
	 * Update an existing content page
	 *
	 * @param int $pageId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updatePage(int $pageId, mixed $object) : mixed
	{
		return self::updateResource('/pages/' . $pageId, $object);
	}

	/**
	 * Delete an existing content page
	 *
	 * @param int $pageId
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deletePage(int $pageId) : mixed
	{
		return self::deleteResource('/pages/' . $pageId);
	}

	/**
	 * Create a Gift Certificate
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createGiftCertificate(mixed $object) : mixed
	{
		return self::createResource('/gift_certificates', $object);
	}

	/**
	 * Get a Gift Certificate
	 *
	 * @param int $giftCertificateId
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getGiftCertificate(int $giftCertificateId) : Resource|string
	{
		return self::getResource('/gift_certificates/' . $giftCertificateId);
	}

	/**
	 * Return the collection of all gift certificates.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getGiftCertificates(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/gift_certificates' . $filter->toQuery());
	}

	/**
	 * Update a Gift Certificate
	 *
	 * @param int $giftCertificateId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateGiftCertificate(int $giftCertificateId, mixed $object) : mixed
	{
		return self::updateResource('/gift_certificates/' . $giftCertificateId, $object);
	}

	/**
	 * Delete a Gift Certificate
	 *
	 * @param int $giftCertificateId
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteGiftCertificate(int $giftCertificateId) : mixed
	{
		return self::deleteResource('/gift_certificates/' . $giftCertificateId);
	}

	/**
	 * Delete all Gift Certificates
	 *
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteAllGiftCertificates() : mixed
	{
		return self::deleteResource('/gift_certificates');
	}

	/**
	 * Create Product Review
	 *
	 * @param int $productId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createProductReview(int $productId, mixed $object) : mixed
	{
		return self::createResource('/products/' . $productId . '/reviews', $object);
	}

	/**
	 * Create Product Bulk Discount rules
	 *
	 * @param int $productId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createProductBulkPricingRules(int $productId, mixed $object) : mixed
	{
		return self::createResource('/products/' . $productId . '/discount_rules', $object);
	}

	/**
	 * Create a Marketing Banner
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createMarketingBanner(mixed $object) : mixed
	{
		return self::createResource('/banners', $object);
	}

	/**
	 * Get all Marketing Banners
	 *
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getMarketingBanners() : array|string
	{
		return self::getCollection('/banners');
	}

	/**
	 * Delete all Marketing Banners
	 *
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteAllMarketingBanners() : mixed
	{
		return self::deleteResource('/banners');
	}

	/**
	 * Delete a specific Marketing Banner
	 *
	 * @param int $bannerId
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteMarketingBanner(int $bannerId) : mixed
	{
		return self::deleteResource('/banners/' . $bannerId);
	}

	/**
	 * Update an existing banner
	 *
	 * @param int $bannerId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateMarketingBanner(int $bannerId, mixed $object) : mixed
	{
		return self::updateResource('/banners/' . $bannerId, $object);
	}

	/**
	 * Add a address to the customer's address book.
	 *
	 * @param int $customerId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCustomerAddress(int $customerId, mixed $object) : mixed
	{
		return self::createResource('/customers/' . $customerId . '/addresses', $object);
	}

	/**
	 * Create a product rule
	 *
	 * @param int $productId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createProductRule(int $productId, mixed $object) : mixed
	{
		return self::createResource('/products/' . $productId . '/rules', $object);
	}

	/**
	 * Create a customer group.
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCustomerGroup(mixed $object) : mixed
	{
		return self::createResource('/customer_groups', $object);
	}

	/**
	 * Get list of customer groups
	 *
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCustomerGroups() : array|string
	{
		return self::getCollection('/customer_groups');
	}

	/**
	 * Delete a customer group
	 *
	 * @param int $customerGroupId
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCustomerGroup(int $customerGroupId) : mixed
	{
		return self::deleteResource('/customer_groups/' . $customerGroupId);
	}

	/**
	 * Delete all customers
	 *
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteAllCustomers() : mixed
	{
		return self::deleteResource('/customers');
	}

	/**
	 * Delete all options
	 *
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteAllOptions() : mixed
	{
		return self::deleteResource('/options');
	}

	/**
	 * Return the option value object that was created.
	 *
	 * @param int $optionId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createOptionValue(int $optionId, mixed $object) : mixed
	{
		return self::createResource('/options/' . $optionId . '/values', $object);
	}

	/**
	 * Delete all option sets that were created.
	 *
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteAllOptionSets() : mixed
	{
		return self::deleteResource('/optionsets');
	}

	/**
	 * Return the option value object that was updated.
	 *
	 * @param int $optionId
	 * @param int $optionValueId
	 * @param mixed $object
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateOptionValue(int $optionId, int $optionValueId, mixed $object) : mixed
	{
		return self::updateResource(
			'/options/' . $optionId . '/values/' . $optionValueId,
			$object
		);
	}

	/**
	 * get list of blog posts
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getBlogPosts(array|int|bool|Filter $filter = false) : array|string
	{
		$filter = Filter::create($filter);
		return self::getCollection('/blog/posts' . $filter->toQuery(), 'BlogPost');
	}

	/**
	 * get a specific blog post by id
	 *
	 * @param 	int $id post id
	 * @return 	Resource|string $object
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getBlogPost(int $id) : Resource|string
	{
		return self::getResource('/blog/posts/' . $id, 'BlogPost');
	}

	/**
	 * create blog post
	 * @param mixed $object post params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createBlogPost(mixed $object) : mixed
	{
		return self::createResource('/blog/posts', $object);
	}

	/**
	 * update blog post
	 * @param int $id post id
	 * @param mixed $object post params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateBlogPost(int $id, mixed $object) : mixed
	{
		return self::updateResource('/blog/posts/' . $id, $object);
	}

	/**
	 * delete a blog post
	 * @param int $id post id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteBlogPost(int $id) : mixed
	{
		return self::deleteResource('/blog/posts/' . $id);
	}
    
    /**
     * Get v3 resource
     * @param string $path path of resource
	 * @return Resource|string resource object or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
     */
    public static function getV3Resource(string $path) : Resource|string
    {
        self::setVersion('v3');
		$ret = self::getResource($path);

        // reset version to v2
        self::setVersion('v2');

        return $ret;
    }
    
	/**
	 * Send a v3 delete api request
	 *
	 * @param string $path api endpoint
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public static function deleteV3Resource(string $path) : mixed
	{
        self::setVersion('v3');
		$ret = self::deleteResource($path);

        // reset version to v2
        self::setVersion('v2');

        return $ret;
	}

	/**
	 * create catalog
     * @param  string $catalogPath path of request
	 * @param mixed $object catalog params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCatalog(string $catalogPath, mixed $object) : mixed
	{
        self::setVersion('v3');
        $ret = self::createResource('/catalog' . $catalogPath, $object);

        // reset version to v2
        self::setVersion('v2');

        return $ret;
    }

	/**
	 * get a specific catalog by id
	 *
     * @param string $catalogPath api endpoint
	 * @return Resource|string
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalog(string $catalogPath) : Resource|string
	{
        return self::getV3Resource('/catalog' . $catalogPath);
    }
    
	/**
	 * Returns the default collection of products.
	 *
     * @param string $catalogPath api endpoint
	 * @param array|int|bool|Filter $filter
	 * @return Resource|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
    public static function getCatalogs(string $catalogPath, array|int|bool|Filter $filter = false) : Resource|string
	{
		$filter = Filter::create($filter);

        return self::getV3Resource('/catalog' . $catalogPath . $filter->toQuery());
    }

	/**
	 * update catalog
	 * @param string $catalogPath api endpoint
	 * @param mixed $object catalog params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCatalog(string $catalogPath, mixed $object) : mixed
	{
        self::setVersion('v3');
        $ret = self::updateResource('/catalog' . $catalogPath, $object);

        // reset version to v2
        self::setVersion('v2');
        
        return $ret;
    }

	/**
	 * delete catalog
	 * @param string $catalogPath api endpoint
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalog(string $catalogPath) : mixed
	{
        return self::deleteV3Resource('/catalog' . $catalogPath);
	}

	/**
	 * Create a new catalog product.
	 *
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCatalogProduct(mixed $object) : mixed
	{
		return self::createCatalog('/products', $object);
	}

	/**
	 * Get single catalog product.
	 *
	 * @param int $id catalog product id
	 * @return Resource|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProduct(int $id) : Resource|string
	{
		return self::getCatalogs('/products/' . $id);
    }
    
	/**
	 * Returns the default collection of catalog products.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return Resource|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProducts(array|int|bool|Filter $filter = false) : Resource|string
	{
		return self::getCatalogs('/products', $filter);
    }

	/**
	 * update catalog product
	 * @param int $id catalog product id
	 * @param mixed $object catalog product params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCatalogProduct(int $id, mixed $object) : mixed
	{
		return self::updateCatalog('/products/' . $id, $object);
	}

	/**
	 * delete a catalog product
     * @param int $id product id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalogProduct(int $id) : mixed
	{
		return self::deleteCatalog('/products/' . $id);
	}
    
	/**
	 * delete catalog products by filter.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return mixed when filter is false | \stdClass
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalogProducts(array|int|bool|Filter $filter = false) : mixed
	{
        if ($filter === false) {
            return false;
        }

        $filter = Filter::create($filter);
		return self::deleteCatalog('/products' . $filter->toQuery());
    }

	/**
	 * Create a new catalog brand.
	 *
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCatalogBrand(mixed $object): mixed
	{
		return self::createCatalog('/brands', $object);
	}

	/**
	 * Get single catalog brand.
	 *
	 * @param int $id catalog brand id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogBrand(int $id) : array|string
	{
		return self::getCatalogs('/brands/' . $id);
    }
    
	/**
	 * Returns the default collection of catalog brands.
	 *
	 * @param array|int|bool|Filter $filter
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogBrands(array|int|bool|Filter $filter = false) : array|string
	{
		return self::getCatalogs('/brands', $filter);
	}

	/**
	 * update catalog brand
	 * @param int $id catalog brand id
	 * @param mixed	$object catalog brand params
	 * @return 	mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCatalogBrand(int $id, mixed $object) : mixed
	{
		return self::updateCatalog('/brands/' . $id, $object);
	}

	/**
	 * delete a catalog brand
     * @param int $id brand id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalogBrand(int $id) : mixed
	{
		return self::deleteCatalog('/brands/' . $id);
	}

	/**
	 * Create a new catalog product meta field.
	 *
     * @param int $id product id
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCatalogProductMetaFields(int $id, mixed $object) : mixed
	{
		return self::createCatalog('/products/' . $id . '/metafields', $object);
    }
    
	/**
	 * Returns catalog product meta field.
	 *
     * @param int $id product id
	 * @param array|int|bool|Filter $filter
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductMetaFields(int $id, array|int|bool|Filter $filter = false) : array|string
	{
		return self::getCatalogs('/products/' . $id . '/metafields', $filter);
	}
    
	/**
	 * Get single catalog product meta field.
	 *
     * @param int $productId product id
     * @param int $id product meta field id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductMetaField(int $productId, int $id) : array|string
	{
		return self::getCatalogs('/products/' . $productId . '/metafields/' . $id);
	}

	/**
	 * update catalog product metafield
     * @param int $productId product id
	 * @param int $id metafield id
	 * @param mixed $object metafield params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCatalogProductMetaField(int $productId, int $id, mixed $object) : mixed
	{
		return self::updateCatalog('/products/' . $productId . '/metafields/' . $id, $object);
	}

	/**
	 * delete a catalog product meta field
     * @param int $productId product id
	 * @param int $id metafield id
	 * @return 	mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalogProductMetaField(int $productId, int $id) : mixed
	{
		return self::deleteCatalog('/products/' . $productId . '/metafields/' . $id);
	}

	/**
	 * Create a new catalog product image.
	 *
     * @param int $id product id
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCatalogProductImage(int $id, mixed $object) : mixed
	{
		return self::createCatalog('/products/' . $id . '/images', $object);
	}

	/**
	 * Get single catalog product image.
	 *
	 * @param int $productId catalog product id
	 * @param int $id catalog product image id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductImage(int $productId, int $id) : array|string
	{
		return self::getCatalogs('/products/' . $productId . '/images/' . $id);
	}

	/**
	 * Returns the default collection of catalog product images.
	 *
	 * @param int $id catalog product id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductImages(int $id, array|int|bool|Filter $filter = false) : array|string
	{
		return self::getCatalogs('/products/' . $id . '/images', $filter);
	}

	/**
	 * update catalog product image
     * @param int $productId product id
	 * @param int $id image id
	 * @param mixed $object image params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCatalogProductImage(int $productId, int $id, mixed $object) : mixed
	{
		return self::updateCatalog('/products/' . $productId . '/images/' . $id, $object);
	}

	/**
	 * delete a catalog product image
     * @param int $productId catalog product id
	 * @param int $id image id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalogProductImage(int $productId, int $id) : mixed
	{
		return self::deleteCatalog('/products/' . $productId . '/images/' . $id);
    }

	/**
	 * Create a new catalog product custom field.
	 *
     * @param int $id product id
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCatalogProductCustomField(int $id, mixed $object) : mixed
	{
		return self::createCatalog('/products/' . $id . '/custom-fields', $object);
	}

	/**
	 * Get single catalog product custom field.
	 *
	 * @param int $productId catalog product id
	 * @param int $id catalog product custom field id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductCustomField(int $productId, int $id) : array|string
	{
		return self::getCatalogs('/products/' . $productId . '/custom-fields/' . $id);
	}

	/**
	 * Returns the default collection of catalog product custom fields.
	 *
	 * @param int $id catalog product id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductCustomFields(int $id, array|int|bool|Filter $filter = false) : array|string
	{
		return self::getCatalogs('/products/' . $id . '/custom-fields', $filter);
	}

	/**
	 * update catalog product custom field
     * @param int $productId product id
	 * @param int $id custom field id
	 * @param mixed $object custom field params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCatalogProductCustomField(int $productId, int $id, mixed $object) : mixed
	{
		return self::updateCatalog('/products/' . $productId . '/custom-fields/' . $id, $object);
	}

	/**
	 * delete a catalog product custom field
     * @param int $productId catalog product id
	 * @param int $id custom field id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalogProductCustomField(int $productId, int $id) : mixed
	{
		return self::deleteCatalog('/products/' . $productId . '/custom-fields/' . $id);
    }

	/**
	 * Create a new catalog product bulk pricing rule.
	 *
     * @param int $id product id
	 * @param mixed $object fields to create
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function createCatalogProductBulkPricingRule(int $id, mixed $object) : mixed
	{
		return self::createCatalog('/products/' . $id . '/bulk-pricing-rules', $object);
	}

	/**
	 * Get single catalog product bulk pricing rule.
	 *
	 * @param int $productId catalog product id
	 * @param int $id catalog product bulk pricing rule id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductBulkPricingRule(int $productId, int $id) : array|string
	{
		return self::getCatalogs('/products/' . $productId . '/bulk-pricing-rules/' . $id);
	}

	/**
	 * Returns the default collection of catalog product bulk pricing rules.
	 *
	 * @param int $id catalog product id
	 * @return array|string list of products or XML string if useXml is true
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function getCatalogProductBulkPricingRules(int $id, array|int|bool|Filter $filter = false) : array|string
	{
		return self::getCatalogs('/products/' . $id . '/bulk-pricing-rules', $filter);
	}

	/**
	 * update catalog product bulk pricing rules
     * @param int $productId product id
	 * @param int $id bulk pricing rules id
	 * @param mixed $object bulk pricing rules params
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function updateCatalogProductBulkPricingRule(int $productId, int $id, mixed $object) : mixed
	{
		return self::updateCatalog('/products/' . $productId . '/bulk-pricing-rules/' . $id, $object);
	}

	/**
	 * delete a catalog product bulk pricing rule
     * @param int $productId catalog product id
	 * @param int $id bulk pricing rule id
	 * @return mixed
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public static function deleteCatalogProductBulkPricingRule(int $productId, int $id) : mixed
	{
		return self::deleteCatalog('/products/' . $productId . '/bulk-pricing-rules/' . $id);
    }
}
