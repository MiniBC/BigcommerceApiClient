<?php
namespace Bigcommerce\Api;

use CurlHandle;

/**
 * HTTP connection.
 */
class Connection
{
	/**
	 * @var CurlHandle cURL resource
	 */
	private CurlHandle $curl;

	/**
	 * @var array<string,string> hash of HTTP request headers
	 */
	private array $headers = [];

	/**
	 * @var array<string,string> hash of headers from HTTP response
	 */
	private array $responseHeaders = [];

	/**
	 * The status line of the response.
	 * @var string|null
	 */
	private string|null $responseStatusLine;

	/**
	 * @var string|null response body
	 */
	private string|null $responseBody;

	/**
	 * @var boolean
	 */
	private bool $failOnError = false;

	/**
	 * Manually follow location redirects. Used if CURLOPT_FOLLOWLOCATION
	 * is unavailable due to open_basedir restriction.
	 * @var boolean
	 */
	private bool $followLocation = false;

	/**
	 * Maximum number of redirects to try.
	 * @var int
	 */
	private int $maxRedirects = 20;

	/**
	 * Number of redirects followed in a loop.
	 * @var int
	 */
	private int $redirectsFollowed = 0;

	/**
	 * Deal with failed requests if failOnError is not set.
	 * @var string|false
	 */
	private mixed $lastError = false;

	/**
	 * Determines whether the response body should be returned as a raw string.
	 */
	private bool $rawResponse = false;

	/**
	 * @var string|null Determines the default content type to use with requests and responses.
	 */
	private string|null $contentType;

	/**
	 * @var bool determines if another attempt should be made if the request
	 * failed due to too many requests.
	 */
	private bool $autoRetry = true;

	/** @var int current count of retry attempts */
	private int $retryAttempts = 0;

	/**
	 * Maximum number of retries for a request before reporting a failure.
	 */
	const MAX_RETRY = 5;

	/**
	 * XML media type.
	 */
	const MEDIA_TYPE_XML = 'application/xml';

	/**
	 * JSON media type.
	 */
	const MEDIA_TYPE_JSON = 'application/json';

	/**
	 * Default urlencoded media type.
	 */
	const MEDIA_TYPE_WWW = 'application/x-www-form-urlencoded';

	/**
	 * Initializes the connection object.
	 */
	public function __construct()
	{
        if (!defined('STDIN')) {
			define('STDIN', fopen('php://stdin', 'r'));
		}

		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, 'parseHeader'));
		curl_setopt($this->curl, CURLOPT_WRITEFUNCTION, array($this, 'parseBody'));

		// Set to a blank string to make cURL include all encodings it can handle (gzip, deflate, identity) in the 'Accept-Encoding' request header and respect the 'Content-Encoding' response header
		curl_setopt($this->curl, CURLOPT_ENCODING, '');

		// using TLSv1 cipher by default
		$this->setCipher('TLSv1');

		if (!ini_get("open_basedir")) {
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		} else {
			$this->followLocation = true;
		}

		$this->setTimeout(60);
	}

	/**
	 * Controls whether requests and responses should be treated
	 * as XML. Defaults to false (using JSON).
	 *
	 * @param bool $option
	 */
	public function useXml(bool $option = true)
	{
		if ($option) {
			$this->contentType = self::MEDIA_TYPE_XML;
			$this->rawResponse = true;
		} else {
			$this->contentType = self::MEDIA_TYPE_JSON;
			$this->rawResponse = false;
		}
	}

	/**
	 * Controls whether requests or responses should be treated
	 * as urlencoded form data.
	 *
	 * @param bool $option
	 */
	public function useUrlencoded(bool $option = true)
	{
		if ($option) {
			$this->contentType = self::MEDIA_TYPE_WWW;
		}
	}

	/**
	 * Throw an exception if the request encounters an HTTP error condition.
	 *
	 * <p>An error condition is considered to be:</p>
	 *
	 * <ul>
	 * 	<li>400-499 - Client error</li>
	 *	<li>500-599 - Server error</li>
	 * </ul>
	 *
	 * <p><em>Note that this doesn't use the builtin CURL_FAILONERROR option,
	 * as this fails fast, making the HTTP body and headers inaccessible.</em></p>
	 *
	 * @param bool $option
	 */
	public function failOnError(bool $option = true)
	{
		$this->failOnError = $option;
	}

	/**
	 * Sets the HTTP basic authentication.
	 *
	 * @param string $username
	 * @param string $password
	 */
	public function authenticateBasic(string $username, string $password)
	{
		$this->removeHeader('X-Auth-Client');
		$this->removeHeader('X-Auth-Token');

		curl_setopt($this->curl, CURLOPT_USERPWD, "$username:$password");
	}

	/**
	 * Sets Oauth authentication headers
	 *
	 * @param string $clientId
	 * @param string $authToken
	 */
	public function authenticateOauth(string $clientId, string $authToken)
	{
		$this->addHeader('X-Auth-Client', $clientId);
		$this->addHeader('X-Auth-Token', $authToken);

		curl_setopt($this->curl, CURLOPT_USERPWD, "");
	}

	/**
	 * Sets the auto retry parameter
	 *
	 * @param bool $retry
	 */
	public function setAutoRetry(bool $retry = true)
	{
		$this->autoRetry = $retry;
	}

	/**
	 * Set a default timeout for the request. The client will error if the
	 * request takes longer than this to respond.
	 *
	 * @param int $timeout number of seconds to wait on a response
	 */
	public function setTimeout(int $timeout)
	{
		curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $timeout);
	}

	/**
	 * Set a proxy server for outgoing requests to tunnel through.
	 *
	 * @param string $server
	 * @param bool|int $port
	 */
	public function useProxy(string $server, bool|int $port = false)
	{
		curl_setopt($this->curl, CURLOPT_PROXY, $server);

		if ($port) {
			curl_setopt($this->curl, CURLOPT_PROXYPORT, $port);
		}
	}

	/**
	 * @todo may need to handle CURLOPT_SSL_VERIFYHOST and CURLOPT_CAINFO as well
	 * @param boolean
	 */
	public function verifyPeer(bool $option = false)
	{
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $option);
	}

	/**
	 * Set which cipher to use during SSL requests.
	 * @param string $cipher the name of the cipher
	 */
	public function setCipher(string $cipher = 'TLSv1')
	{
		curl_setopt($this->curl, CURLOPT_SSL_CIPHER_LIST, $cipher);
	}

	/**
	 * Add a custom header to the request.
	 *
	 * @param string $header
	 * @param string $value
	 */
	public function addHeader(string $header, string $value)
	{
		$this->headers[$header] = "$header: $value";
	}

	/**
	 * Removes a custom header from the request
	 *
	 * @param string $header
	 */
	public function removeHeader(string $header)
	{
		if (isset($this->headers[$header])) {
			unset($this->headers[$header]);
		}
	}

	/**
	 * Get the MIME type that should be used for this request.
	 *
	 * Defaults to JSON.
	 */
	private function getContentType() : string
	{
		return ($this->contentType) ?: self::MEDIA_TYPE_JSON;
	}

	/**
	 * Clear previously cached request data and prepare for
	 * making a fresh request.
	 */
	private function initializeRequest()
	{
		$this->responseBody = '';
		$this->responseHeaders = array();
		$this->lastError = false;
		$this->addHeader('Accept', $this->getContentType());
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
	}

	/**
	 * Check the response for possible errors and handle the response body returned.
	 *
	 * If failOnError is true, a client or server error is raised, otherwise returns false
	 * on error.
	 *
     * @return mixed
	 * @throws NetworkError
	 * @throws ClientError
	 * @throws ServerError
	 */
	private function handleResponse() : mixed
	{
		if (curl_errno($this->curl)) {
			throw new NetworkError(curl_error($this->curl), curl_errno($this->curl));
		}

		$body = ($this->rawResponse) ? $this->getBody() : json_decode($this->getBody());

		$status = $this->getStatus();

		if ($status >= 400 && $status <= 499) {
			if ($this->failOnError) {
				if (is_object($body) && property_exists($body, 'error')) {
					throw new ClientError($body->error, $status);
				}

				if (is_object($body) && property_exists($body, 'errors')) {
                    if (is_array($body->errors)) {
                        $error = $body->errors[0];
                    } else {
                        $error = $body->errors;
                    }

                    if (is_object($error) && property_exists($error, 'title')) {
                        throw new ClientError($error->title, $status);
                    }
                }

				throw new ClientError($body, $status);
			}

            $this->lastError = $body;

            return false;
		} elseif ($status >= 500 && $status <= 599) {
			if ($this->failOnError) {
				throw new ServerError($body, $status);
			}

            $this->lastError = $body;

            return false;
		}

		// reset retry attempts on a successful request
		$this->retryAttempts = 0;

		if ($this->followLocation) {
			$this->followRedirectPath();
		}

		return $body;
	}

	/**
	 * Return an representation of an error returned by the last request, or false
	 * if the last request was not an error.
     *
     * @return mixed
	 */
	public function getLastError() : mixed
	{
		return $this->lastError;
	}

	/**
	 * Recursively follow redirect until an OK response is received or
	 * the maximum redirects limit is reached.
	 *
	 * Only 301 and 302 redirects are handled. Redirects from POST and PUT requests will
	 * be converted into GET requests, as per the HTTP spec.
	 *
	 * @throws NetworkError
	 * @throws ClientError
	 * @throws ServerError
	 */
	private function followRedirectPath()
	{
		$this->redirectsFollowed++;

		if ($this->getStatus() == 301 || $this->getStatus() == 302) {
			if ($this->redirectsFollowed < $this->maxRedirects) {
				$location = $this->getHeader('Location');
				$forwardTo = parse_url($location);

				if (isset($forwardTo['scheme']) && isset($forwardTo['host'])) {
					$url = $location;
				} else {
					$forwardFrom = parse_url(curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL));
					$url = $forwardFrom['scheme'] . '://' . $forwardFrom['host'] . $location;
				}

				$this->get($url);
			} else {
				$errorString = "Too many redirects when trying to follow location.";
				throw new NetworkError($errorString, CURLE_TOO_MANY_REDIRECTS);
			}
		} else {
			$this->redirectsFollowed = 0;
		}
	}

	/**
	 * Make an HTTP GET request to the specified endpoint.
	 *
	 * @param string $url
	 * @param array $query
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public function get(string $url, array $query = []) : mixed
	{
		$this->initializeRequest();

		$requestUrl = $url;

		if (is_array($query) && !empty($query)) {
			$requestUrl .= '?' . http_build_query($query);
		}

		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($this->curl, CURLOPT_URL, $requestUrl);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_PUT, false);
		curl_setopt($this->curl, CURLOPT_HTTPGET, true);
		curl_exec($this->curl);

		try {
			return $this->handleResponse();
		} catch (ClientError $ce) {
			if ($this->canRetryRequest($ce)) {
				$delayMs = (int)$this->getHeader('X-Rate-Limit-Time-Reset-Ms');
				usleep($delayMs * 1000);

				return $this->get($url, $query);
			}

			throw $ce;
		} catch (NetworkError $ne) {
			if ($this->canRetryNetworkError($ne)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->get($url, $query);
			}

			throw $ne;
		} catch (ServerError $se) {
			if ($this->canRetryServerError($se)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->get($url, $query);
			}

			throw $se;
		}
	}

	/**
	 * Make an HTTP POST request to the specified endpoint.
	 *
	 * @param string $url
	 * @param mixed $body
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws ServerError
	 * @throws NetworkError
	 */
	public function post(string $url, mixed $body) : mixed
	{
		$contentType = $this->getContentType();
		$this->addHeader('Content-Type', $contentType);

        if ($contentType === self::MEDIA_TYPE_JSON) {
            $postData = json_encode($body);
        } else {
            $postData = http_build_query($body, '', '&');
        }

		$this->initializeRequest();

		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_PUT, false);
		curl_setopt($this->curl, CURLOPT_HTTPGET, false);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $postData);
		curl_exec($this->curl);

		try {
			return $this->handleResponse();
		} catch (ClientError $ce) {
			if ($this->canRetryRequest($ce)) {
				$delayMs = (int)$this->getHeader('X-Rate-Limit-Time-Reset-Ms');
				usleep($delayMs * 1000);

				return $this->post($url, $body);
			}

			throw $ce;
		} catch (NetworkError $ne) {
			if ($this->canRetryNetworkError($ne)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->post($url, $body);
			}

			throw $ne;
		} catch (ServerError $se) {
			if ($this->canRetryServerError($se)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->post($url, $body);
			}

			throw $se;
		}
	}

	/**
	 * Make an HTTP HEAD request to the specified endpoint.
	 *
	 * @param string $url
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public function head(string $url) : mixed
	{
		$this->initializeRequest();

		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_NOBODY, true);
		curl_exec($this->curl);

		try {
			return $this->handleResponse();
		} catch (ClientError $ce) {
			if ($this->canRetryRequest($ce)) {
				$delay = (int)$this->getHeader('x-retry-after');
				sleep($delay);

				return $this->head($url);
			}

			throw $ce;
		} catch (NetworkError $ne) {
			if ($this->canRetryNetworkError($ne)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->head($url);
			}

			throw $ne;
		} catch (ServerError $se) {
			if ($this->canRetryServerError($se)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->head($url);
			}

			throw $se;
		}
	}

	/**
	 * Make an HTTP PUT request to the specified endpoint.
	 *
	 * Requires a tmpfile() handle to be opened on the system, as the cURL
	 * API requires it to send data.
	 *
	 * @param string $url
	 * @param mixed $body
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public function put(string $url, mixed $body) : mixed
	{
		$this->addHeader('Content-Type', $this->getContentType());

		$bodyData = json_encode($body);

		$this->initializeRequest();

		$handle = tmpfile();
		fwrite($handle, $bodyData);
		fseek($handle, 0);
		curl_setopt($this->curl, CURLOPT_INFILE, $handle);
		curl_setopt($this->curl, CURLOPT_INFILESIZE, strlen($bodyData));

		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_HTTPGET, false);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_PUT, true);
		curl_exec($this->curl);

		fclose($handle);
		curl_setopt($this->curl, CURLOPT_INFILE, STDIN);

		try {
			return $this->handleResponse();
		} catch (ClientError $ce) {
			if ($this->canRetryRequest($ce)) {
				$delayMs = (int)$this->getHeader('X-Rate-Limit-Time-Reset-Ms');
				usleep($delayMs * 1000);

				return $this->put($url, $body);
			}

			throw $ce;
		} catch (NetworkError $ne) {
			if ($this->canRetryNetworkError($ne)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->put($url, $body);
			}

			throw $ne;
		} catch (ServerError $se) {
			if ($this->canRetryServerError($se)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->put($url, $body);
			}

			throw $se;
		}
	}

	/**
	 * Make an HTTP DELETE request to the specified endpoint.
	 *
	 * @param string $url
	 * @return mixed
	 *
	 * @throws ClientError
	 * @throws NetworkError
	 * @throws ServerError
	 */
	public function delete(string $url) : mixed
	{
		$this->initializeRequest();

		curl_setopt($this->curl, CURLOPT_PUT, false);
		curl_setopt($this->curl, CURLOPT_POST, false);
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_exec($this->curl);

		try {
			return $this->handleResponse();
		} catch (ClientError $ce) {
			if ($this->canRetryRequest($ce)) {
				$delayMs = (int)$this->getHeader('X-Rate-Limit-Time-Reset-Ms');
				usleep($delayMs * 1000);

				return $this->delete($url);
			}

			throw $ce;
		} catch (NetworkError $ne) {
			if ($this->canRetryNetworkError($ne)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->delete($url);
			}

			throw $ne;
		} catch (ServerError $ne) {
			if ($this->canRetryServerError($ne)) {
				sleep(60);
				$this->retryAttempts++;

				return $this->delete($url);
			}

			throw $ne;
		}
	}

	/**
	 * Callback methods collects header lines from the response.
	 *
	 * @param CurlHandle $curl
	 * @param string $headers
	 * @return int
	 */
	private function parseHeader(CurlHandle $curl, string $headers) : int
	{
		if (!$this->responseStatusLine && str_starts_with($headers, 'HTTP/')) {
			$this->responseStatusLine = $headers;
		} else {
			$parts = explode(': ', $headers);
			if (isset($parts[1])) {
				$this->responseHeaders[strtolower($parts[0])] = trim($parts[1]);
			}
		}

		return strlen($headers);
	}

	/**
	 * Callback method collects body content from the response.
	 *
	 * @param CurlHandle $curl
	 * @param string $body
	 * @return int
	 */
	private function parseBody(CurlHandle $curl, string $body) : int
	{
		$this->responseBody .= $body;

		return strlen($body);
	}

	/**
	 * returns true if another attempt should be made on the request
	 *
	 * @param ClientError $ce
	 * @return bool
	 */
	private function canRetryRequest(ClientError $ce) : bool
	{
		return ($this->autoRetry && in_array((int)$ce->getCode(), array( 408, 429 )));
	}

	/**
	 * returns true if another attempt should be made
	 *
	 * @param ServerError $se
	 * @return bool
	 */
	private function canRetryServerError(ServerError $se) : bool
	{
		if (
			$this->autoRetry
			&& in_array((int)$se->getCode(), array( 500, 502 ))
			&& $this->retryAttempts < self::MAX_RETRY
		) {
			return true;
		}

		return false;
	}

    /**
     * returns true if another attempt should be made
     *
     * @param NetworkError $ne
     * @return bool
     */
	private function canRetryNetworkError(NetworkError $ne) : bool
	{
		if (
			$this->autoRetry
			&& in_array((int)$ne->getCode(), array( CURLE_OPERATION_TIMEDOUT, CURLE_GOT_NOTHING, CURLE_RECV_ERROR, CURLE_SSL_CONNECT_ERROR ))
			&& $this->retryAttempts < self::MAX_RETRY
		) {
			return true;
		}

		return false;
	}

	/**
	 * Access the status code of the response.
	 * @return string
	 */
	public function getStatus() : string
	{
		return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
	}

	/**
	 * Access the message string from the status line of the response.
	 * @return string
	 */
	public function getStatusMessage() : string 
	{
		return $this->responseStatusLine;
	}

	/**
	 * Access the content body of the response
     *
     * @return string
	 */
	public function getBody() : string
	{
		return $this->responseBody;
	}

	/**
	 * Access given header from the response.
	 *
	 * @param string $header
	 * @return string|bool
	 */
	public function getHeader(string $header) : string|bool
	{
		$header = strtolower($header);

		if (array_key_exists($header, $this->responseHeaders)) {
			return $this->responseHeaders[$header];
		}

		return false;
	}

	/**
	 * Return the full list of response headers
	 */
	public function getHeaders() : array
	{
		return $this->responseHeaders;
	}

	/**
	 * Close the cURL resource when the instance is garbage collected
	 */
	public function __destruct()
	{
		curl_close($this->curl);
	}
}
