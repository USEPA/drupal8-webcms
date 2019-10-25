#!/bin/bash

set -euo pipefail

org_name=epa-prototyping
app_name=webcms-proto

if test "$BUILDKITE_PULL_REQUEST" != "false"; then
  echo 'Skipping deployment on pull requests'
fi

CF_DOCKER_PASSWORD="$AWS_SECRET_ACCESS_KEY"
export CF_DOCKER_PASSWORD

# Define a logout function - this allows us to invoke `./cf logout' even if a command trips
# the error-exit option.
logout() {
  ./cf logout
}

# Only log commands after we've obtained authorization (or else the console will see the token)
set -x

tag="$BUILDKITE_BRANCH-$BUILDKITE_BUILD_NUMBER"
image_name="$DOCKER_REPOSITORY:$tag"

echo '--- Authenticating'

curl -Ls 'https://packages.cloudfoundry.org/stable?release=linux64-binary&source=github' > \
  cf-cli.tgz

tar xzf cf-cli.tgz cf

./cf api 'https://api.fr.cloud.gov'
./cf auth

# Register the exit handler now that we've successfully logged in.
trap logout EXIT

./cf target -o "$org_name" -s "$app_name"

echo '--- Deploying'

# Run app push - if CloudFoundry reports a failure, capture logs and upload them to Buildkite.
if ! ./cf push "$app_name" --docker-username "$AWS_ACCESS_KEY_ID" --docker-image "$image_name"; then
  exit_code=$?

  ./cf logs "$app_name" --recent > cf-recent.txt
  buildkite-agent artifact upload cf-recent.txt

  echo "^^^ +++"
  echo "Build failed. Logs have been uploaded." > /dev/stderr
  exit "$exit_code"
fi

echo '--- Running Drush'

# Re-use the image tag for this deployment: we can quickly find the task name based on
# the build metadata.
./cf run-task "$app_name" 'sh /var/www/html/scripts/cloudfoundry/update.sh' --name "$tag"

# Spin loop: CF doesn't appear to have a wait API for tasks, so we have to check manually.
while true; do
  status="$(./cf tasks "$app_name" | env APP="$tag" awk '$2 == ENVIRON["APP"] { print $3 }')"

  case "$status" in
    RUNNING)
      sleep 5
      ;;

    SUCCEEDED|FAILED)
      ./cf logs "$app_name" --recent | grep "TASK/$tag" > task-logs.txt
      buildkite-agent artifact upload task-logs.txt

      if test "$status" == FAILED; then
        echo "^^^ +++"
        echo "Drush update failed. Logs have been uploaded." > /dev/stderr
        exit 1
      fi

      break
      ;;

    *)
      echo "Unknown status: $status" > /dev/stderr
      ;;
  esac
done
