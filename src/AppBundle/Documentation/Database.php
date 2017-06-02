<?php


/**
 * @api {get} /search Search
 * @apiVersion 1.0.0
 * @apiGroup Database
 * @apiName Search
 * @apiDescription This is a function for search the words in bbdd
 * @apiParam {String} text for search in bbdd
 * *      HTTP/1.1 200 OK
 *      {
 *          "results": [
 *              {
 *                  ref:{json}
 *              },
 *              { . . . }
 *          ]
 *      }
 */

/**
 * @api {get} /save Save
 * @apiVersion 1.0.0
 * @apiGroup Database
 * @apiName Save
 * @apiDescription This is a function for save the ref with word in bbdd.
 * @apiParam {String} ref for return of the save in blockchain
 * @apiParam {String} input "word or phrase of ref
 *
 *  * *      HTTP/1.1 200 OK
 *      {
 *          "results": [
 *              {
 *                  OK
 *              }
 *          ]
 *      }
 *  * *      HTTP/1.1 404 OK
 *      {
 *          "results": [
 *              {
 *                  error
 *              }
 *          ]
 *      }
 */
