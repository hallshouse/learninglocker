<?php

use Jenssegers\Mongodb\Model as Eloquent;

class Report extends Eloquent {

  /**
   * Our MongoDB collection used by the model.
   *
   * @var string
   */
  protected $collection = 'reports';
  public static $rules = [];
  protected $fillable = ['name', 'description', 'query', 'lrs', 'since', 'until'];

  public function getFilterAttribute() {
    $reportArr = $this->toArray();
    $filter = [];

    if (isset($reportArr['query'])) $filter['filter'] = json_encode($reportArr['query']);
    if (isset($reportArr['since'])) {
      $filter['since'] = (new \Carbon\Carbon($reportArr['since']))->toIso8601String();
    }
    if (isset($reportArr['until'])) {
      $filter['until'] = (new \Carbon\Carbon($reportArr['until']))->toIso8601String();
    }

    return $filter;
  }

  public function getMatchAttribute() {
    $reportArr = $this->toArray();
    $match = [];
    $query = isset($reportArr['query']) ? (array) $reportArr['query'] : null;

    if (is_array($query) && count($query) > 0 && !isset($query[0])) {
      foreach ($query as $key => $value) {
        if (is_array($value)) {
          $match[$key] = ['$in' => $value];
        } else {
          $match[$key] = $value;
        }
      }
    }

    $since = isset($reportArr['since']) ? (new \Carbon\Carbon($reportArr['since']))->toIso8601String() : null;
    $until = isset($reportArr['until']) ? (new \Carbon\Carbon($reportArr['until']))->toIso8601String() : null;

    if ($since || $until) {
      $match['statement.timestamp'] = [];
    }
    if ($since) {
      $match['statement.timestamp']['$gte'] = $since;
    }
    if ($until) {
      $match['statement.timestamp']['$lte'] = $until;
    }

    return $match;
  }

  public function getWhereAttribute() {
    $reportArr = $this->toArray();
    $wheres = [];
    $query = isset($reportArr['query']) ? (array) $reportArr['query'] : null;

    if (is_array($query) && count($query) > 0 && !isset($query[0])) {
      $wheres = array_map(function ($key) use ($query) {
        if (is_array($query[$key])) {
          return [$key, 'in', $query[$key]];
        } else {
          return [$key, '=', $query[$key]];
        }
      }, array_keys($query));
    }

    $since = isset($reportArr['since']) ? (new \Carbon\Carbon($reportArr['since']))->toIso8601String() : null;
    $until = isset($reportArr['until']) ? (new \Carbon\Carbon($reportArr['until']))->toIso8601String() : null;

    if ($since && $until) {
      $wheres[] = ['statement.timestamp', 'between', $since, $until];
    } else if ($since) {
      $wheres[] = ['statement.timestamp', '>=', $since];
    } else if ($until) {
      $wheres[] = ['statement.timestamp', '<=', $until];
    }

    return $wheres;
  }

  public function toArray() {
    return (array) \Locker\Helpers\Helpers::replaceHtmlEntity(parent::toArray());
  }

}