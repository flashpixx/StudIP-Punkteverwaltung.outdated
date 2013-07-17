parallel Multi-Agent-Simulation (MAS)
=====================================

This project simulates [multiagents](http://en.wikipedia.org/wiki/Multi-agent_system) written in [LUA](http://www.lua.org). The agent code is stored in a Postgres
database like a repository and a service daemon completes the working jobs. The service can be spawned with MPI over a cluster system. An own
client creates the access to the database with a possiblity caching of the project data. The database stores different agent projects with
their agent and project data. Each working task is a subset of agent and data and will be calculate by the daemon, all data of an agent can
be logged to the task, so after finish the data can be viewed in the client. The client can show data with OpenGL and Lua function can be used
for different exploring functions, also the data can be exported via HDF.

The project is in the conception phase, so there is no testing / compiled source. I would like to create massive parallel simulation
for multi agents, which can defined in [LUA](http://www.lua.org). With a UI client (written in [Qt](http://qt-project.org/)) a
modelling tool should be created. The agent theory is reclined on the definition on the work of Michael Wooldrige - An introduction to MultiAgent Systems

I hope for some assistance...
