Repository Setup
================

To fully utilize the automation and documentation features provided by FastForward DevTools, consumer repositories require specific configurations in GitHub.

GitHub Pages
------------

FastForward DevTools automatically generates and deploys reports, test coverage, and API documentation to GitHub Pages.

1.  Navigate to your repository on GitHub.
2.  Go to **Settings** > **Pages**.
3.  Under **Build and deployment** > **Branch**:
    *   Select the ``gh-pages`` branch.
    *   Select ``/ (root)`` as the folder.
    *   Click **Save**.

.. note::
   The ``gh-pages`` branch is automatically created and updated by the ``reports.yml`` workflow. If the branch does not exist yet, run the workflow manually once or wait for the first push to ``main``.

GitHub Wiki
-----------

The wiki synchronization feature allows you to maintain documentation in Markdown within your repository and have it automatically published to the GitHub Wiki.

.. important::
   **Initial Manual Step Required**
   GitHub does not create the underlying wiki repository until at least one page is created via the web interface. You **MUST** create an initial ``Home.md`` page manually before using ``dev-tools wiki --init`` or any automated sync features.

1.  Navigate to your repository on GitHub.
2.  Click the **Wiki** tab.
3.  Click **Create the first page** (or **New Page**).
4.  Ensure the title is ``Home`` and add some initial content.
5.  Click **Save Page**.

Once this is done, the wiki can be cloned as a submodule and synchronized by the DevTools commands.

Workflow Permissions
--------------------

GitHub Actions must have permission to push changes to your repository and, for
the changelog release flow, to open release-preparation pull requests.

1.  Go to **Settings** > **Actions** > **General**.
2.  Scroll down to **Workflow permissions**.
3.  Select **Read and write permissions**.
4.  Enable **Allow GitHub Actions to create and approve pull requests**.
5.  Click **Save**.

.. warning::
   Without these permissions, the ``wiki.yml`` and ``reports.yml`` workflows will fail when attempting to deploy content, and the ``changelog.yml`` workflow will fail when trying to open a ``release/v...`` pull request.

.. note::
   If the permission controls are disabled or grayed out, the repository is
   usually constrained by organization policy or by missing admin access. This
   is separate from branch protection. Branch protection affects whether the
   generated release pull request can be merged, while workflow permissions
   affect whether GitHub Actions can create that pull request at all.
