// Standard concrete mix-design estimation:
// dry volume = wet (cast) volume x 1.54 (accounts for voids/shrinkage between dry ingredients)
// cement density ~1440 kg/m3, one bag = 50 kg
const DRY_VOLUME_FACTOR = 1.54;
const CEMENT_DENSITY_KG_M3 = 1440;
const BAG_WEIGHT_KG = 50;

function calculateMixQuantities(wetVolumeM3, ratioCement, ratioSand, ratioGravel) {
  const ratioSum = ratioCement + ratioSand + ratioGravel;
  const dryVolume = wetVolumeM3 * DRY_VOLUME_FACTOR;

  const cementVolume = (dryVolume * ratioCement) / ratioSum;
  const sandVolume = (dryVolume * ratioSand) / ratioSum;
  const gravelVolume = (dryVolume * ratioGravel) / ratioSum;

  const cementWeightKg = cementVolume * CEMENT_DENSITY_KG_M3;
  const cementBags = cementWeightKg / BAG_WEIGHT_KG;

  return {
    wetVolume: wetVolumeM3,
    dryVolume,
    cementBags,
    cementWeightKg,
    sandVolume,
    gravelVolume,
  };
}

function stripFootingVolume(length, width, depth, count) {
  return length * width * depth * count;
}

function isolatedFootingVolume(length, width, depth, count) {
  return length * width * depth * count;
}

function matFoundationVolume(length, width, depth) {
  return length * width * depth;
}

function rectColumnVolume(width, depth, height, count) {
  return width * depth * height * count;
}

function circleColumnVolume(diameter, height, count) {
  const radius = diameter / 2;
  return Math.PI * radius * radius * height * count;
}

function slabVolume(length, width, thickness, count) {
  return length * width * thickness * count;
}
