Architecture Decision Records
=============================

Architecture decision records capture durable context for choices that affect
Fast Forward DevTools users and consumer repositories. ADRs explain why a
decision exists, not every implementation detail.

Format
------

Each ADR uses the same compact structure:

- status: proposed, accepted, superseded, or deprecated;
- context: the problem and constraints at the time of the decision;
- decision: the chosen direction;
- consequences: trade-offs, follow-up responsibilities, and known limits.

Naming Convention
-----------------

Use a four-digit sequence and a short kebab-case topic:
``0001-example-topic.rst``. New ADRs should be appended rather than inserted so
existing references remain stable.

Records
-------

.. toctree::
   :maxdepth: 1

   0001-wiki-preview-branches
   0002-reports-pages-previews
   0003-sync-preserves-consumer-customizations
   0004-resource-stubs-call-reusable-workflows
