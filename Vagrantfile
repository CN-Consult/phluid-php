Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-16.04"
  config.vm.box_version = "201801.02.0"
  config.vm.box_check_update = false
  config.vm.provision "shell", path: "VagrantProvision.sh"

  # Port of the example apps
  config.vm.network "forwarded_port", guest: 4000, host: 4000
end
