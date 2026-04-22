const board = require('../shared/project-board-client.cjs');

/**
 * @param {{github: import('@actions/github/lib/utils').GitHub, context: any, core: any}} deps
 *
 * @returns {Promise<void>}
 */
module.exports = async function transitionStatus({ github, context, core }) {
    const includeCurrentPullRequest = 'true' === (process.env.INPUT_INCLUDE_CURRENT_PULL_REQUEST ?? '').toLowerCase();
    const fromStatus = process.env.INPUT_FROM_STATUS;
    const toStatus = process.env.INPUT_TO_STATUS;

    const project = await board.loadConfiguredProject(
        github,
        context.repo.owner,
        process.env.INPUT_PROJECT,
    );

    if (!project) {
        core.info('No configured GitHub Project V2 was resolved. Skipping status transition.');

        return;
    }

    const statusField = board.getSingleSelectField(project, 'Status');
    const targetOption = board.getSingleSelectOption(project, 'Status', toStatus);

    if (!statusField || !targetOption) {
        core.info(`Project "${project.title}" does not expose the expected target status "${toStatus}".`);

        return;
    }

    const result = await github.graphql(
        `query($owner: String!, $repo: String!, $pullRequestNumber: Int!) {
          repository(owner: $owner, name: $repo) {
            issues(first: 100, orderBy: {field: UPDATED_AT, direction: DESC}, states: CLOSED) {
              nodes {
                number
                projectItems(first: 20) {
                  nodes {
                    id
                    project {
                      ... on ProjectV2 {
                        id
                      }
                    }
                    fieldValues(first: 20) {
                      nodes {
                        __typename
                        ... on ProjectV2ItemFieldSingleSelectValue {
                          field {
                            ... on ProjectV2SingleSelectField {
                              name
                            }
                          }
                          name
                        }
                      }
                    }
                  }
                }
              }
            }
            pullRequests(first: 100, orderBy: {field: UPDATED_AT, direction: DESC}, states: [MERGED, CLOSED]) {
              nodes {
                number
                projectItems(first: 20) {
                  nodes {
                    id
                    project {
                      ... on ProjectV2 {
                        id
                      }
                    }
                    fieldValues(first: 20) {
                      nodes {
                        __typename
                        ... on ProjectV2ItemFieldSingleSelectValue {
                          field {
                            ... on ProjectV2SingleSelectField {
                              name
                            }
                          }
                          name
                        }
                      }
                    }
                  }
                }
              }
            }
            pullRequest(number: $pullRequestNumber) {
              number
              projectItems(first: 20) {
                nodes {
                  id
                  project {
                    ... on ProjectV2 {
                      id
                    }
                  }
                  fieldValues(first: 20) {
                    nodes {
                      __typename
                      ... on ProjectV2ItemFieldSingleSelectValue {
                        field {
                          ... on ProjectV2SingleSelectField {
                            name
                          }
                        }
                        name
                      }
                    }
                  }
                }
              }
            }
          }
        }`,
        {
            owner: context.repo.owner,
            repo: context.repo.repo,
            pullRequestNumber: context.payload.pull_request?.number ?? 0,
        },
    );

    const moveToStatus = async (item, label) => {
        if (!item || board.getExistingFieldValue(item, 'Status') !== fromStatus) {
            return;
        }

        await board.updateSingleSelectField(
            github,
            project.id,
            item.id,
            statusField.id,
            targetOption.id,
        );

        core.info(`${label} moved to ${toStatus}.`);
    };

    if (includeCurrentPullRequest) {
        await moveToStatus(
            board.findProjectItem(result.repository.pullRequest?.projectItems?.nodes ?? [], project.id),
            `Pull request #${context.payload.pull_request.number}`,
        );
    }

    for (const pullRequest of result.repository.pullRequests.nodes) {
        await moveToStatus(
            board.findProjectItem(pullRequest.projectItems.nodes, project.id),
            `PR #${pullRequest.number}`,
        );
    }

    for (const issue of result.repository.issues.nodes) {
        await moveToStatus(
            board.findProjectItem(issue.projectItems.nodes, project.id),
            `Issue #${issue.number}`,
        );
    }
};
