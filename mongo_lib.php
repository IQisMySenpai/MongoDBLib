<?php
require_once __DIR__ . '/vendor/autoload.php';

/**
 * A class to handle the connection to the MongoDB database. It also contains functions to perform common database
 * operations, which were simplified to make them easier to use.
 * @package MongoLib
 * @author Jannick Schröer
 * @version 1.0.0
 * @since 1.0.0
 */
class MongoLib {
    private string $username = ''; // The username to connect to the database.
    private string $password = ''; // The password to connect to the database.
    private string $domain = ''; // The domain to connect to the database.
    private string $db_name = ''; // The name of the database to connect to.

    public MongoDB\Client $client;
    public MongoDB\Database $database;

    /**
     * MongoLib constructor.
     */
    function __construct() {
        $this->connect();
    }

    /**
     * Connects to the database.
     *
     * @since 1.0.0
     *
     * @return void The connection is stored in $this->client. As well as the database in $this->database.
     */
    function connect(): void {
        $this->client = new MongoDB\Client(
            sprintf('mongodb://%s:%s@%s',
                $this->username,
                $this->password,
                $this->domain)
        );

        try {
            $this->client->listDatabases();
        }  catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
            http_response_code(500);
            echo 'The database server is not available.';
            exit(1);
        }
        $this->database = $this->client->{$this->db_name};
    }

    /**
     * Finds documents in a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to search.
     * @param $filter array|string|null The filter to apply to the query.
     * @param $options array|string|null The options to apply to the query. Note: The typeMap has a default value.
     * @return array The documents found.
     */
    function find(string $collection, array|string $filter = null, array|string $options = null): array {
        $this->checkDataFormat($filter);
        $this->checkDataFormat($options);

        $this->typeMapDefault($options);

        $results = $this->database->{$collection}->find($filter, $options);
        return iterator_to_array($results);
    }

    /**
     * Counts the number of documents in a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to search.
     * @param $filter array|string|null The filter to apply to the query.
     * @param $options array|string|null The options to apply to the query.
     * @return int The number of documents found.
     */
    function count($collection, $filter = null, $options = null): int {
        $this->checkDataFormat($filter);
        $this->checkDataFormat($options);

        return $this->database->{$collection}->countDocuments($filter, $options);
    }

    /**
     * Inserts a single document into a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to insert into.
     * @param $document array|string The document to insert.
     * @return string The id of the inserted document.
     */
    function insertOne(string $collection, array|string $document): string {
        if (is_string($document)) {
            $document = json_decode($document, true);
        }

        $result = $this->database->{$collection}->insertOne($document);
        return $result->getInsertedId();
    }

    /**
     * Inserts multiple documents into a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to insert into.
     * @param $documents array The documents to insert.
     * @return array The ids of the inserted documents.
     */
    function insertMany(string $collection, array $documents): array {
        $results = $this->database->{$collection}->insertMany($documents);
        return $results->getInsertedIds();
    }

    /**
     * Deletes a single document from a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to delete from.
     * @param $filter array|string The filter to apply to the query.
     * @return int The number of documents deleted.
     */
    function deleteOne(string $collection, array|string $filter): int {
        $this->checkDataFormat($filter);
        $result = $this->database->{$collection}->deleteOne($filter);
        return $result->getDeletedCount();
    }

    /**
     * Deletes multiple documents from a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to delete from.
     * @param $filter array|string The filter to apply to the query.
     * @return int The number of documents deleted.
     */
    function deleteMany(string $collection, array|string $filter): int {
        $this->checkDataFormat($filter);
        $results = $this->database->{$collection}->deleteMany($filter);
        return $results->getDeletedCount();
    }

    /**
     * Updates a single document in a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to update.
     * @param $filter array|string The filter to apply to the query.
     * @param $update array|string The update to apply to the document.
     * @param $options array|string|null The options to apply to the query.
     * @return int The number of documents updated.
     */
    function updateOne(string $collection, array|string$filter, array|string$update, array|string $options = null): int {
        $this->checkDataFormat($filter);
        $this->checkDataFormat($update);
        $this->checkDataFormat($options);

        $result = $this->database->{$collection}->updateOne($filter, $update, $options);
        return $result->getModifiedCount();
    }

    /**
     * Updates multiple documents in a collection.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to update.
     * @param $filter array|string The filter to apply to the query.
     * @param $update array|string The update to apply to the document.
     * @param $options array|string|null The options to apply to the query.
     * @return int The number of documents updated.
     */
    function updateMany(string $collection, array|string $filter, array|string $update, array|string $options = null): int {
        $this->checkDataFormat($filter);
        $this->checkDataFormat($update);
        $this->checkDataFormat($options);

        $results = $this->database->{$collection}->updateMany($filter, $update, $options);
        return $results->getModifiedCount();
    }

    /**
     * Performs a bulk write operation.
     *
     * @since 1.0.0
     *
     * @param $collection string The collection to update.
     * @param $operations array The operations to perform.
     * @param $options array|string|null The options to apply to the query.
     */
    function bulkWrite(string $collection, array $operations, array|string $options = null): int {
        $this->checkDataFormat($options);

        $results = $this->database->{$collection}->bulkWrite($operations, $options);
        return $results->getModifiedCount();
    }

    /**
     * Converts a string to a MongoDB\BSON\ObjectId.
     *
     * @since 1.0.0
     *
     * @param $id string The string to convert.
     * @return \MongoDB\BSON\ObjectId The converted string.
     */
    static function stringToObjectId(string $id): MongoDB\BSON\ObjectId {
        return new MongoDB\BSON\ObjectId($id);
    }

    /**
     * Checks if the data is a string and converts it to an array.
     *
     * @since 1.0.0
     *
     * @param $data array|string The data to check. This is passed by reference.
     * @return void
     */
    static function checkDataFormat(array|string &$data): void
    {
        if (empty($data)) {
            $data = [];
        } elseif (is_string($data)) {
            $data = json_decode($data, true);
        }
    }

    /**
     * Sets the typeMap to an array if it is not set.
     *
     * @since 1.0.0
     *
     * @param $data array The data to check. This is passed by reference.
     * @return void
     */
    static function typeMapDefault(array &$data = []): void {
        if (!array_key_exists('typeMap', $data)) {
            $data['typeMap'] = [
                'array'=>'array',
                'document'=>'array',
                'root'=>'array'
            ];
        }
    }
}