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

Normal sync generates local API Markdown in ``.dev-tools/wiki-generated``
without a submodule. The direct Wiki workflow generates a read-only artifact on
pull requests and publishes directly from ``main`` to the separate GitHub Wiki
repository.

.. important::
   **Initial Manual Step Required**
   GitHub does not create the underlying Wiki repository until at least one
   page is created via the web interface. Create an initial ``Home`` page before
   automated publication or legacy ``wiki --init``. Local Markdown generation
   does not require this remote initialization.

1.  Navigate to your repository on GitHub.
2.  Click the **Wiki** tab.
3.  Click **Create the first page** (or **New Page**).
4.  Ensure the title is ``Home`` and add some initial content.
5.  Click **Save Page**.

Once this is done, the direct workflow can clone the separate Wiki repository
for publication. Only legacy users who explicitly choose ``wiki --init`` need
a local submodule.

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
   Publication jobs need their declared write permissions. The external
   Changelog caller needs permission to open its managed version PR, but
   automatically publishes the tag and Release after its reviewed managed
   version PR is merged. Preserve any
   reviewed GitHub App/token policy instead of broadening every workflow token.

.. note::
   If the permission controls are disabled or grayed out, the repository is
   usually constrained by organization policy or by missing admin access. This
   is separate from branch protection. Branch protection affects whether the
   generated release pull request can be merged, while workflow permissions
   affect whether GitHub Actions can create that pull request at all.
